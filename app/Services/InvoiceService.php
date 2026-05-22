<?php
// app/Services/InvoiceService.php
namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Models\Site;
use App\Models\Invoice;
use App\Models\Employee;
use App\Models\ShiftDate;
use App\Models\InvoiceItem;
use App\Models\Subcontractor;
use App\Models\LeaveRequest;

class InvoiceService
{
    /**
     * Resolve the live billing rate for a single shift date.
     *
     * Why live: shifts.site_rate is captured once at creation, so later edits to
     * Site office_rate or additions to site_holiday_rates would otherwise never
     * reach the invoice. This lookup is the single source of truth for billing.
     *
     * Priority: holiday rate for that date → site.office_rate → client.office_rate → 0.
     */
    protected function resolveClientHourlyRate($shiftDate, $client)
    {
        $site = $shiftDate->shift->site ?? null;

        if ($site) {
            $holidayRate = $site->siteHolidayRates
                ->first(function ($r) use ($shiftDate) {
                    $hd = $r->holiday_date;
                    $hdStr = $hd instanceof Carbon ? $hd->format('Y-m-d') : (string) $hd;
                    return $hdStr === Carbon::parse($shiftDate->shift_date)->format('Y-m-d');
                });

            if ($holidayRate && !is_null($holidayRate->site_rate)) {
                return (float) $holidayRate->site_rate;
            }

            if (!is_null($site->office_rate)) {
                return (float) $site->office_rate;
            }
        }

        return (float) ($client->office_rate ?? 0);
    }

    public function generateClientInvoice($clientId, $siteId, $dateFrom, $dateTo, $dueDate, $notes = null, $frequency = null)
    {
        // Normalize and validate dates to Y-m-d strings
        $dateFrom = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : Carbon::today()->toDateString();
        $dateTo = $dateTo ? Carbon::parse($dateTo)->toDateString() : Carbon::today()->toDateString();

        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            throw new \InvalidArgumentException('dateFrom must be before or equal to dateTo');
        }

        \Log::info('Invoice Generation Debug', [
            'clientId' => $clientId,
            'siteId' => $siteId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);

        // Resolve client record whether the caller passed user_id or client.id
        $client = Client::where('user_id', $clientId)->first();
        if (! $client) {
            $client = Client::find($clientId);
        }

        if (! $client) {
            // If still not found, assume $clientId was actually the user's id
            $clientUserId = $clientId;
        } else {
            $clientUserId = $client->user_id ?? $clientId;
        }

        // Fetch ShiftDate rows for this client (by client user id) and the given site.
        // Eager-load siteHolidayRates so resolveClientHourlyRate() doesn't N+1.
        $shiftDates = ShiftDate::with(['shift.site.siteHolidayRates', 'staff'])
            ->whereHas('shift', function ($q) use ($clientUserId, $siteId) {
                $q->where('client_id', $clientUserId)
                  ->where('site_id', $siteId);
            })
            ->whereDate('shift_date', '>=', $dateFrom)
            ->whereDate('shift_date', '<=', $dateTo)
            ->orderBy('shift_date')
            ->get();

        try {
            $debugPath = storage_path('logs/invoice_debug.log');
            $payload = [
                'timestamp' => now()->toDateTimeString(),
                'context' => 'generateClientInvoice',
                'clientId_param' => $clientId,
                'resolved_client_user_id' => $clientUserId,
                'siteId' => $siteId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'shiftDateCount' => $shiftDates->count(),
                'shiftIds' => $shiftDates->pluck('shift_id')->unique()->values()->all(),
                'shift_samples' => $shiftDates->take(20)->map(function($sd) use ($client) {
                    // Debug: use same billing rate logic as invoice generation
                    $hourlyRate = $this->resolveClientHourlyRate($sd, $client);
                    $computed = [];
                    try {
                        // For client-facing debug we compute using scheduled shift hours
                        $computed = $this->processShiftDate($sd, $hourlyRate, true);
                    } catch (\Throwable $e) {
                        $computed = ['error' => $e->getMessage()];
                    }

                    return [
                        'id' => $sd->id,
                        'shift_id' => $sd->shift_id,
                        'shift_date' => $sd->shift_date,
                        'start_time' => $sd->start_time,
                        'end_time' => $sd->end_time,
                        'break_time' => $sd->break_time,
                        'absentee_start_time' => $sd->absentee_start_time,
                        'absentee_end_time' => $sd->absentee_end_time,
                        'guard_rate' => $sd->guard_rate,
                        'resolved_hourly_rate' => $hourlyRate,
                        'computed' => $computed,
                    ];
                })->values()->all(),
            ];
            file_put_contents($debugPath, json_encode($payload, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore
        }

        if ($shiftDates->isEmpty()) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('No shift dates found for the selected client/site and date range');
        }

        $invoiceItems = [];
        $totalHours = 0;
        $totalBreaks = 0;
        $totalBookOnHours = 0;
        $totalBookOffHours = 0;
        $totalAmount = 0; // Track actual billed amount

        foreach ($shiftDates as $shiftDate) {
            // Resolve live billing rate (holiday → site → client). Frozen shifts.site_rate is intentionally ignored.
            $hourlyRate = $this->resolveClientHourlyRate($shiftDate, $client);

            // For client invoices, bill based on scheduled shift times (ignore book on/off)
            $item = $this->processShiftDate($shiftDate, $hourlyRate, true);
            $invoiceItems[] = $item;

            $totalHours += $item['hours'] + $item['break_hours'] + $item['book_on_hours'] + $item['book_off_hours'];
            $totalBreaks += $item['break_hours'];
            $totalBookOnHours += $item['book_on_hours'];
            $totalBookOffHours += $item['book_off_hours'];
            $totalAmount += $item['amount']; // Sum actual invoice item amounts
        }

        $totalDeductionsHours = $totalBreaks + $totalBookOnHours + $totalBookOffHours;
        $averageRate = ($totalHours - $totalDeductionsHours) > 0
            ? $totalAmount / ($totalHours - $totalDeductionsHours)
            : ($client->office_rate ?? 0);

        $invoice = Invoice::create([
            'type' => 'client',
            'client_id' => $clientId,
            'site_id' => $siteId,
            'issue_date' => now(),
            'due_date' => $dueDate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'notes' => $notes,
            'payment_note' => $client->payment_terms,
            'rate_per_hour' => $averageRate,
            'total_shift_hours' => $totalHours - $totalDeductionsHours,
            'total_duration_hours' => $totalHours,
            'total_break_hours' => $totalBreaks,
            'total_deductions_hours' => $totalDeductionsHours,
            'gross_amount' => $totalAmount,
            'net_amount' => $totalAmount,
            'frequency' => $frequency,
        ]);

        foreach ($invoiceItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }

    /**
     * Generate a single client invoice that aggregates shifts across multiple sites.
     * If $siteIds is empty it will attempt to find all sites for the client.
     */
    public function generateClientInvoiceForSites($clientId, array $siteIds = [], $dateFrom = null, $dateTo = null, $dueDate = null, $notes = null, $frequency = null)
    {
        // Resolve client whether caller passed a client model id or the client's user id
        $client = Client::where('user_id', $clientId)->first();
        if (! $client) {
            $client = Client::find($clientId);
        }

        if (! $client) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Client not found');
        }

        $clientUserId = $client->user_id ?? $clientId;

        // Normalize and validate dates
        $dateFrom = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : Carbon::today()->toDateString();
        $dateTo = $dateTo ? Carbon::parse($dateTo)->toDateString() : Carbon::today()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            throw new \InvalidArgumentException('dateFrom must be before or equal to dateTo');
        }

        if (empty($siteIds)) {
            $siteIds = Site::where('client_id', $clientUserId)->pluck('id')->toArray();
        }

        $invoiceItems = [];
        $totalHours = 0;
        $totalBreaks = 0;
        $totalBookOnHours = 0;
        $totalBookOffHours = 0;
        $totalAmount = 0; // Track actual billed amount

        // Fetch all ShiftDate rows for these sites (client-scoped) in one query and group by site.
        // Eager-load siteHolidayRates so the per-date rate lookup doesn't N+1.
        $shiftDates = ShiftDate::with(['shift.site.siteHolidayRates', 'staff'])
            ->whereHas('shift', function ($q) use ($clientUserId, $siteIds) {
                $q->where('client_id', $clientUserId)
                  ->whereIn('site_id', $siteIds);
            })
            ->whereDate('shift_date', '>=', $dateFrom)
            ->whereDate('shift_date', '<=', $dateTo)
            ->orderBy('shift_date')
            ->get();

        // Group by site id so we can iterate site-by-site and include all shifts for each site
        $grouped = $shiftDates->groupBy(function ($sd) {
            return $sd->shift->site_id ?? null;
        });

        foreach ($grouped as $siteId => $datesForSite) {
            foreach ($datesForSite as $shiftDate) {
                // Resolve live billing rate (holiday → site → client). Frozen shifts.site_rate is intentionally ignored.
                $hourlyRate = $this->resolveClientHourlyRate($shiftDate, $client);
                // For client invoices across sites, bill based on scheduled shift times
                $item = $this->processShiftDate($shiftDate, $hourlyRate, true);
                $invoiceItems[] = $item;

                $totalHours += $item['hours'] + $item['break_hours'] + $item['book_on_hours'] + $item['book_off_hours'];
                $totalBreaks += $item['break_hours'];
                $totalBookOnHours += $item['book_on_hours'];
                $totalBookOffHours += $item['book_off_hours'];
                $totalAmount += $item['amount']; // Sum actual invoice item amounts
            }
        }

        if (empty($invoiceItems)) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('No shift dates found in the selected date range for any of the selected sites');
        }

        $totalDeductionsHours = $totalBreaks + $totalBookOnHours + $totalBookOffHours;
        $averageRate = ($totalHours - $totalDeductionsHours) > 0 
            ? $totalAmount / ($totalHours - $totalDeductionsHours) 
            : ($client->office_rate ?? 0);

        // Create single invoice for all sites. site_id left null to indicate multiple sites.
        $invoice = Invoice::create([
            'type' => 'client',
            'client_id' => $clientId,
            'site_id' => null,
            'issue_date' => now(),
            'due_date' => $dueDate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'notes' => $notes ? $notes . ' | Sites: ' . json_encode($siteIds) : 'Sites: ' . json_encode($siteIds),
            'payment_note' => $client->payment_terms,
            'rate_per_hour' => $averageRate,
            'total_shift_hours' => $totalHours - $totalDeductionsHours,
            'total_duration_hours' => $totalHours,
            'total_break_hours' => $totalBreaks,
            'total_deductions_hours' => $totalDeductionsHours,
            'gross_amount' => $totalAmount,
            'net_amount' => $totalAmount,
            'frequency' => $frequency,
        ]);

        foreach ($invoiceItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }


    public function generateSubcontractorInvoice($subcontractorId, $dateFrom, $dateTo, $dueDate, $notes = null, $securityStaffId = null)
    {
        // Attempt to resolve the requested subcontractor id/record without throwing early
        $subcontractor = null; // may be a User model if the caller passed a user id
        $requestedId = $subcontractorId;
        if ($requestedId) {
            // Try to find a User first (common case: caller passed user id)
            $subcontractor = User::find($requestedId);
        }

        // Normalize dates
        $dateFrom = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : Carbon::today()->toDateString();
        $dateTo = $dateTo ? Carbon::parse($dateTo)->toDateString() : Carbon::today()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            throw new \InvalidArgumentException('dateFrom must be before or equal to dateTo');
        }

        // Fetch all ShiftDate rows that belong to this subcontractor within the date range.
        $shiftDatesQuery = ShiftDate::with(['shift.site', 'staff.employee'])
            ->where('is_assign', 4)
            ->whereDate('shift_date', '>=', $dateFrom)
            ->whereDate('shift_date', '<=', $dateTo);

        // Filter by subcontractor via multiple possible storage locations
        if ($subcontractorId) {
            $shiftDatesQuery->where(function ($q) use ($subcontractorId) {
                $q->where('subcontractor_id', $subcontractorId)
                    ->orWhereHas('shift', function ($qs) use ($subcontractorId) {
                        $qs->where('subcontractor_id', $subcontractorId);
                    })
                    ->orWhereHas('staff.employee', function ($qe) use ($subcontractorId) {
                        $qe->where('subcontractor', $subcontractorId)
                           ->orWhereRaw('JSON_CONTAINS(`subcontractor`, ?)', [json_encode($subcontractorId)])
                           ->orWhere('subcontractor', 'like', '%'. $subcontractorId .'%');
                    });
            });
        }

        if (!empty($securityStaffId)) {
            $shiftDatesQuery->where('staff_id', $securityStaffId);
        }

        $shiftDates = $shiftDatesQuery->get();

        // Ensure we only include shift dates that actually belong to the requested subcontractor
        if ($requestedId) {
            $shiftDates = $shiftDates->filter(function ($sd) use ($requestedId) {
                // Resolve subcontractor identifier(s) present on the shift date/shift/staff
                $candidates = [];

                if (! empty($sd->subcontractor_id)) $candidates[] = $sd->subcontractor_id;
                if (! empty($sd->shift) && ! empty($sd->shift->subcontractor_id)) $candidates[] = $sd->shift->subcontractor_id;

                // staff -> employee -> subcontractor may be stored in various formats
                try {
                    if (! empty($sd->staff) && ! empty($sd->staff->employee)) {
                        $emp = $sd->staff->employee;
                        if (! empty($emp->subcontractor)) {
                            // numeric or JSON array or comma list
                            if (is_numeric($emp->subcontractor)) {
                                $candidates[] = $emp->subcontractor;
                            } else {
                                $as = $emp->subcontractor;
                                // try JSON
                                $decoded = null;
                                try { $decoded = json_decode($as, true); } catch (\Throwable $_) { $decoded = null; }
                                if (is_array($decoded)) {
                                    foreach ($decoded as $d) { $candidates[] = $d; }
                                } else {
                                    // parse comma-separated
                                    foreach (preg_split('/[,;\\s]+/', (string)$as) as $part) {
                                        if (strlen(trim($part))) $candidates[] = trim($part);
                                    }
                                }
                            }
                        }
                    }
                } catch (\Throwable $_) {
                    // ignore
                }

                // Normalize all candidates to strings for comparison
                $candidates = array_filter(array_map(function ($v) { return $v === null ? null : (string) $v; }, $candidates));
                $requested = (string) $requestedId;

                return in_array($requested, $candidates, true);
            })->values();
        }

        // Determine subcontractor commission percent (snapshot)
        $subcontractorRecord = Subcontractor::where('user_id', $subcontractorId)->first();

        // Fallback: maybe $subcontractorId is actually the subcontractor.id (not the user id)
        if (! $subcontractorRecord && is_numeric($subcontractorId)) {
            $subcontractorRecord = Subcontractor::find($subcontractorId);
        }

        // Subcontractor default rate if available
        $subDefaultRate = $subcontractorRecord->rate ?? null;

        $invoiceItems = [];
        $totalHours = 0;
        $totalAmount = 0;

        foreach ($shiftDates as $shiftDate) {
            // Subcontractor payroll is always based on the site's guard rate,
            // falling back to the shift-date's stored guard rate when the site
            // has no rate configured. Treat 0/empty as "not set" so a stale 0
            // on a shift date doesn't zero out the item.
            $siteGuardRate = $shiftDate->shift->site->guard_rate ?? null;
            $shiftDateGuardRate = $shiftDate->guard_rate;

            if (! empty($siteGuardRate)) {
                $hourlyRate = $siteGuardRate;
            } elseif (! empty($shiftDateGuardRate)) {
                $hourlyRate = $shiftDateGuardRate;
            } else {
                $hourlyRate = 0;
            }

            $item = $this->processShiftDate($shiftDate, $hourlyRate);
            $invoiceItems[] = $item;

            $totalHours += $item['hours'];
            $totalAmount += $item['amount'];
        }
        // Fallback: check if the User model has a subcontractor relation (user->subcontractor)
        if (! $subcontractorRecord && isset($subcontractor) && method_exists($subcontractor, 'subcontractor')) {
            try {
                $rel = $subcontractor->subcontractor; // may be null
                if ($rel) {
                    $subcontractorRecord = $rel;
                    // update default rate if found
                    $subDefaultRate = $subDefaultRate ?? ($subcontractorRecord->rate ?? null);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $commissionPercent = 0;
        if ($subcontractorRecord && isset($subcontractorRecord->commission)) {
            $commissionPercent = floatval($subcontractorRecord->commission);
        }

        $commissionAmount = round($totalAmount * ($commissionPercent / 100), 2);
        $staffAmount = round($totalAmount - $commissionAmount, 2);
        
        // Determine a sensible payment note: prefer subcontractor model, then user, otherwise null
        $paymentNote = null;
        if (! empty($subcontractor) && isset($subcontractor->payment_terms)) $paymentNote = $subcontractor->payment_terms;
        if (empty($paymentNote) && isset($subcontractorRecord) && isset($subcontractorRecord->payment_terms)) $paymentNote = $subcontractorRecord->payment_terms;

        $invoiceData = [
            'type' => 'subcontractor',
            'subcontractor_id' => $subcontractorId,
            'issue_date' => now(),
            'due_date' => $dueDate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_amount' => $totalAmount,
            'commission_percent' => $commissionPercent,
            'commission_amount' => $commissionAmount,
            'staff_amount' => $staffAmount,
            'status' => 'draft',
            'notes' => $notes,
            'payment_note' => $paymentNote,
            'rate_per_hour' => $totalHours > 0 ? $totalAmount / $totalHours : 0,
            'total_shift_hours' => $totalHours,
        ];

        // If this invoice targets a security staff member, record it on the invoice
        if (! empty($securityStaffId)) {
            $invoiceData['security_staff_id'] = $securityStaffId;
        }

        $invoice = Invoice::create($invoiceData);

        foreach ($invoiceItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }

    public function generateSecurityStaffInvoice($staffId, $site_id, $dateFrom, $dateTo, $dueDate, $notes = null)
    {
        $staff = User::findOrFail($staffId);

        // Normalize and validate dates to Y-m-d
        $dateFrom = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : Carbon::today()->toDateString();
        $dateTo = $dateTo ? Carbon::parse($dateTo)->toDateString() : Carbon::today()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            throw new \InvalidArgumentException('dateFrom must be before or equal to dateTo');
        }

        $shiftDatesQuery = ShiftDate::where('staff_id', $staffId)
            ->whereDate('shift_date', '>=', $dateFrom)
            ->whereDate('shift_date', '<=', $dateTo)
            ->with('shift.site');

        if (!empty($site_id)) {
            $shiftDatesQuery->whereHas('shift', function ($query) use ($site_id) {
                $query->where('site_id', $site_id);
            });
        }

        $shiftDates = $shiftDatesQuery->get();


        $invoiceItems = [];
        $totalHours = 0;
        $totalAmount = 0;

        foreach ($shiftDates as $shiftDate) {
            // Staff payroll: prefer the historical rate stored on the ShiftDate to preserve
            // past billing when rates are edited later. If no ShiftDate guard_rate exists,
            // fall back to the site's guard_rate. No further fallbacks are used here.
            if (!is_null($shiftDate->guard_rate)) {
                $hourlyRate = $shiftDate->guard_rate;
            } else {
                $hourlyRate = $shiftDate->shift->site->guard_rate ?? 0;
            }

            $item = $this->processShiftDate($shiftDate, $hourlyRate);
            $invoiceItems[] = $item;

            $totalHours += $item['hours'];
            $totalAmount += $item['amount'];
        }

        $invoice = Invoice::create([
            'type' => 'security_staff',
            'security_staff_id' => $staffId,
            'subcontractor_id' => $staff->subcontractor_id,
            'issue_date' => now(),
            'site_id' => $site_id,
            'due_date' => empty($dueDate) ? now() : $dueDate,
            'date_from' => $dateFrom,
            'date_to' => empty($dateTo) ? now() : $dateTo,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'notes' => $notes,
            'payment_note' => $staff->subcontractor->payment_terms ?? null,
            'rate_per_hour' => $totalHours > 0 ? $totalAmount / $totalHours : 0,
            'total_shift_hours' => $totalHours,
        ]);

        foreach ($invoiceItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }

    protected function processShiftDate($shiftDate, $hourlyRate, $useScheduledHours = false)
    {
        // Normalize shift_date string defensively — handle malformed day/month ordering
        $rawDate = (string) ($shiftDate->shift_date ?? '');
        // If format is YYYY-MM-DD but middle part looks like a day (>12), swap day/month
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawDate, $m)) {
            $y = (int) $m[1];
            $p2 = (int) $m[2];
            $p3 = (int) $m[3];
            if ($p2 > 12 && $p3 <= 12) {
                $rawDate = sprintf('%04d-%02d-%02d', $y, $p3, $p2);
            }
        }
        // If format is DD-MM-YYYY or DD/MM/YYYY convert to YYYY-MM-DD
        if (preg_match('/^(\d{2})[\-\/](\d{2})[\-\/](\d{4})$/', $rawDate, $m2)) {
            $d = (int) $m2[1];
            $mo = (int) $m2[2];
            $y = (int) $m2[3];
            // Only convert when month looks valid
            if ($mo >= 1 && $mo <= 12) {
                $rawDate = sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
        }

        $date = Carbon::parse($rawDate);
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->start_time);
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->end_time);

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        $breakHours = ($shiftDate->break_time ?? 0) / 60;

        // Compute shift duration first so we can clamp absentee deductions
        $totalHours = $start->diffInMinutes($end) / 60;

        // Defensive defaults
        $bookOnHours = 0;
        $bookOffHours = 0;

        // Compute absentee (book on/off) hours only when we are billing using actual attendance.
        // For client invoices we want to bill based on the scheduled shift times, so callers
        // can pass $useScheduledHours = true to ignore these deductions.
        if (! $useScheduledHours) {
            // Compute absentee (book on) hours if within the shift
            if (! empty($shiftDate->absentee_start_time)) {
                $absStart = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $date->format('Y-m-d') . ' ' . $shiftDate->absentee_start_time
                );

                $graceStart = $start->copy()->subMinutes(15);

                if ($absStart->between($graceStart, $end)) {
                    if ($absStart->lt($start)) {
                        // Early but within grace → no deduction
                        $bookOnHours = 0;
                    } elseif ($absStart->gt($start)) {
                        // Late → deduct from scheduled start
                        $bookOnHours = $start->diffInMinutes($absStart) / 60;
                    }
                }
            }

            // Compute absentee (book off) hours if within the shift
            if (! empty($shiftDate->absentee_end_time)) {
                $absEnd = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->absentee_end_time);
                if ($absEnd->between($start, $end)) {
                    $bookOffHours = $absEnd->diffInMinutes($end) / 60;
                }
            }
        }

        // Clamp absentee deductions to non-negative and not greater than available shift time
        $maxDeductable = max(0, $totalHours - $breakHours);
        $bookOnHours = max(0, min($bookOnHours, $maxDeductable));
        $bookOffHours = max(0, min($bookOffHours, $maxDeductable - $bookOnHours));

        // Payable hours must be non-negative
        $payableHours = max(0, $totalHours - $breakHours - $bookOnHours - $bookOffHours);
        $amount = $payableHours * $hourlyRate;

        return [
            'shift_id' => $shiftDate->shift_id,
            'shift_date_id' => $shiftDate->id,
            'security_staff_id' => $shiftDate->staff_id,
            'site_id' => $shiftDate->shift->site_id,
            'date' => $date->format('Y-m-d'),
            'description' => "Security services at " . ($shiftDate->shift->site->site_name ?? 'Unknown site') . " on {$date->format('Y-m-d')}",
            'start_time' => $shiftDate->start_time,
            'end_time' => $shiftDate->end_time,
            'hours' => $payableHours,
            'break_hours' => $breakHours,
            'book_on_hours' => $bookOnHours,
            'book_off_hours' => $bookOffHours,
            'rate' => $hourlyRate,
            'amount' => $amount,
        ];
    }

    public function calculatePayroll(Employee $staff, $siteId = null, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $endDate   = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

        // 1️⃣ Get all relevant shifts for this staff
        $shiftDatesQuery = ShiftDate::where('staff_id', $staff->user_id)
            ->whereDate('shift_date', '>=', $startDate->toDateString())
            ->whereDate('shift_date', '<=', $endDate->toDateString());

        if ($siteId) {
            $shiftDatesQuery->whereHas('shift', fn($q) => $q->where('site_id', $siteId));
        }

        $shiftDates = $shiftDatesQuery->get();

        $totalHours = $totalBreaks = $totalBookOnHours = $totalBookOffHours = 0;

        foreach ($shiftDates as $shiftDate) {
            // Normalize again inside alternate code path
            $rawDate = (string) ($shiftDate->shift_date ?? '');
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawDate, $m)) {
                $y = (int) $m[1];
                $p2 = (int) $m[2];
                $p3 = (int) $m[3];
                if ($p2 > 12 && $p3 <= 12) {
                    $rawDate = sprintf('%04d-%02d-%02d', $y, $p3, $p2);
                }
            }
            if (preg_match('/^(\d{2})[\-\/](\d{2})[\-\/](\d{4})$/', $rawDate, $m2)) {
                $d = (int) $m2[1];
                $mo = (int) $m2[2];
                $y = (int) $m2[3];
                if ($mo >= 1 && $mo <= 12) {
                    $rawDate = sprintf('%04d-%02d-%02d', $y, $mo, $d);
                }
            }

            $date = Carbon::parse($rawDate);

            $startDT = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->start_time);
            $endDT   = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->end_time);

            if ($endDT->lt($startDT)) $endDT->addDay(); // overnight shift

            $breakMinutes = $shiftDate->break_time ?? 0;
            $shiftDurationMinutes = $startDT->diffInMinutes($endDT);

            // Worked hours minus breaks
            $workedMinutes = max(0, $shiftDurationMinutes - $breakMinutes);
            $totalHours += $workedMinutes / 60;
            $totalBreaks += $breakMinutes / 60;

            // Deduct absentee hours if within shift
            if ($shiftDate->absentee_start_time) {
                $absStart = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->absentee_start_time);
                if ($absStart->between($startDT, $endDT)) {
                    $totalBookOnHours += $startDT->diffInMinutes($absStart) / 60;
                }
            }
            if ($shiftDate->absentee_end_time) {
                $absEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->absentee_end_time);
                if ($absEnd->between($startDT, $endDT)) {
                    $totalBookOffHours += $absEnd->diffInMinutes($endDT) / 60;
                }
            }
        }

        $rate = $staff->guard_rate ?? 0;
        $grossAmount = $totalHours * $rate;
        $deductions  = ($totalBookOnHours + $totalBookOffHours) * $rate;

        // 2️⃣ Handle approved leaves
        $leaves = LeaveRequest::where('employee_id', $staff->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        $sspAmount = $sspDays = $holidayAmount = $holidayHours = $unpaidAmount = $unpaidHours = 0;

        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse(max($leave->start_date, $startDate));
            $leaveEnd   = Carbon::parse(min($leave->end_date, $endDate));

            switch ($leave->leave_type) {
                case 'sick':
                    if ($leave->ssp_days) {
                        $sspDays += $leave->ssp_days;
                        $sspAmount += $leave->ssp_days * 23.75; // SSP rate
                    }
                    break;

                case 'holiday':
                    // Use days * 8 to compute whole-day leave rather than diffInHours which may misbehave with datetimes
                    $requestedHours = (int) ($leaveStart->diffInDays($leaveEnd) + 1) * 8;
                    $earnedHoliday = $staff->holiday_balance ?? 0;

                    $paidHours = min($requestedHours, $earnedHoliday);
                    $unpaidHoursLeave = max(0, $requestedHours - $earnedHoliday);

                    $holidayHours += max(0, $paidHours);
                    $holidayAmount += max(0, $paidHours * $rate);
                    $deductions += max(0, $unpaidHoursLeave * $rate);

                    // Update holiday balance
                    $staff->holiday_balance = max(0, $earnedHoliday - $paidHours);
                    $staff->save();
                    break;

                case 'unpaid':
                    $hours = (int) ($leaveStart->diffInDays($leaveEnd) + 1) * 8;
                    $hours = max(0, $hours);
                    $unpaidHours += $hours;
                    $unpaidAmount += $hours * $rate;
                    $deductions += $hours * $rate;
                    break;

                case 'other':
                    $hours = (int) ($leaveStart->diffInDays($leaveEnd) + 1) * 8;
                    $hours = max(0, $hours);
                    if ($leave->paid) {
                        $holidayHours += $hours;
                        $holidayAmount += $hours * $rate;
                    } else {
                        $unpaidHours += $hours;
                        $unpaidAmount += $hours * $rate;
                        $deductions += $hours * $rate;
                    }
                    break;
            }
        }

        $netAmount = $grossAmount - $deductions + $sspAmount + $holidayAmount;

        return [
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'rate'                 => $rate,
            'total_hours'          => $totalHours,
            'total_breaks'         => $totalBreaks,
            'total_book_on_hours'  => $totalBookOnHours,
            'total_book_off_hours' => $totalBookOffHours,
            'gross_amount'         => $grossAmount,
            'deductions'           => $deductions,
            'net_amount'           => $netAmount,
            'ssp_amount'           => $sspAmount,
            'ssp_days'             => $sspDays,
            'holiday_amount'       => $holidayAmount,
            'holiday_hours'        => $holidayHours,
            'unpaid_leave_amount'  => $unpaidAmount,
            'unpaid_leave_hours'   => $unpaidHours,
            'shift_count'          => $shiftDates->count(),
        ];
    }

    /**
     * Sick Pay (SSP)
     */
    public function calculateSickPay(Employee $staff, Carbon $sickStart, Carbon $sickEnd, int $weeklyPay)
    {
        $totalDays = $sickStart->diffInDays($sickEnd) + 1;

        // Not eligible if weekly pay below threshold
        if ($weeklyPay < 123) {
            return [
                'eligible'    => false,
                'total_days'  => $totalDays,
                'unpaid_days' => $totalDays,
                'paid_days'   => 0,
                'amount'      => 0,
            ];
        }

        $waitingDays = 3;
        $maxWeeks    = 28;

        $unpaidDays = min($waitingDays, $totalDays);
        $paidDays   = max(0, $totalDays - $waitingDays);
        $paidDays   = min($paidDays, $maxWeeks * 7); // cap at 28 weeks

        $sspRate = 23.75;

        return [
            'eligible'    => true,
            'total_days'  => $totalDays,
            'unpaid_days' => $unpaidDays,
            'paid_days'   => $paidDays,
            'amount'      => $paidDays * $sspRate,
        ];
    }

    /**
     * Holiday entitlement
     */
    public function calculateHoliday(Employee $staff, float $workedHours, string $type = 'accrual')
    {
        if ($type === 'accrual') {
            return ['holiday_hours' => round($workedHours * 0.1207, 2)];
        }

        $startDate = Carbon::parse($staff->start_date ?? now());
        $daysWorked = $startDate->diffInDays(now());
        $holidayDays = (28 / 365) * $daysWorked;

        return ['holiday_hours' => round($holidayDays * 8, 2)];
    }
}

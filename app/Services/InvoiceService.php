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
use App\Models\LeaveRequest;

class InvoiceService
{
    public function generateClientInvoice($clientId, $siteId, $dateFrom, $dateTo, $dueDate, $notes = null, $frequency = null)
    {
        $client = Client::where('user_id', $clientId)->first();
        $shift = Shift::where('client_id', $clientId)
            ->where('site_id', $siteId)
            ->firstOrFail();

        $shiftDates = ShiftDate::where('shift_id', $shift->id)
            ->whereBetween('shift_date', [$dateFrom, $dateTo])
            ->orderBy('shift_date')
            ->get();

        $invoiceItems = [];
        $totalHours = 0;
        $totalBreaks = 0;
        $totalBookOnHours = 0;
        $totalBookOffHours = 0;

        foreach ($shiftDates as $shiftDate) {
            $item = $this->processShiftDate($shiftDate, $client->office_rate);
            $invoiceItems[] = $item;

            $totalHours += $item['hours'] + $item['break_hours'] + $item['book_on_hours'] + $item['book_off_hours'];
            $totalBreaks += $item['break_hours'];
            $totalBookOnHours += $item['book_on_hours'];
            $totalBookOffHours += $item['book_off_hours'];
        }

        $totalDeductionsHours = $totalBreaks + $totalBookOnHours + $totalBookOffHours;
        $grossAmount = ($totalHours - $totalBreaks) * $client->office_rate;
        $netAmount = $grossAmount - (($totalBookOnHours + $totalBookOffHours) * $client->office_rate);

        $invoice = Invoice::create([
            'type' => 'client',
            'client_id' => $clientId,
            'site_id' => $siteId,
            'issue_date' => now(),
            'due_date' => $dueDate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_amount' => $netAmount,
            'status' => 'draft',
            'notes' => $notes,
            'payment_note' => $client->payment_terms,
            'rate_per_hour' => $client->office_rate,
            'total_shift_hours' => $totalHours - $totalDeductionsHours,
            'total_duration_hours' => $totalHours,
            'total_break_hours' => $totalBreaks,
            'total_deductions_hours' => $totalDeductionsHours,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'frequency' => $frequency, // <-- store frequency here
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
        $client = Client::where('user_id', $clientId)->first();
        if (!$client) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Client not found');
        }

        if (empty($siteIds)) {
            $siteIds = Site::where('client_id', $clientId)->pluck('id')->toArray();
        }

        $invoiceItems = [];
        $totalHours = 0;
        $totalBreaks = 0;
        $totalBookOnHours = 0;
        $totalBookOffHours = 0;

        foreach ($siteIds as $siteId) {
            $shift = Shift::where('client_id', $clientId)
                ->where('site_id', $siteId)
                ->first();

            if (!$shift) {
                // no shift for this site; skip
                continue;
            }

            $shiftDates = ShiftDate::where('shift_id', $shift->id)
                ->whereBetween('shift_date', [$dateFrom, $dateTo])
                ->orderBy('shift_date')
                ->get();

            foreach ($shiftDates as $shiftDate) {
                $item = $this->processShiftDate($shiftDate, $client->office_rate);
                $invoiceItems[] = $item;

                $totalHours += $item['hours'] + $item['break_hours'] + $item['book_on_hours'] + $item['book_off_hours'];
                $totalBreaks += $item['break_hours'];
                $totalBookOnHours += $item['book_on_hours'];
                $totalBookOffHours += $item['book_off_hours'];
            }
        }

        if (empty($invoiceItems)) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('No shifts found for selected sites');
        }

        $totalDeductionsHours = $totalBreaks + $totalBookOnHours + $totalBookOffHours;
        $grossAmount = ($totalHours - $totalBreaks) * $client->office_rate;
        $netAmount = $grossAmount - (($totalBookOnHours + $totalBookOffHours) * $client->office_rate);

        // Create single invoice for all sites. site_id left null to indicate multiple sites.
        $invoice = Invoice::create([
            'type' => 'client',
            'client_id' => $clientId,
            'site_id' => null,
            'issue_date' => now(),
            'due_date' => $dueDate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_amount' => $netAmount,
            'status' => 'draft',
            'notes' => $notes ? $notes . ' | Sites: ' . json_encode($siteIds) : 'Sites: ' . json_encode($siteIds),
            'payment_note' => $client->payment_terms,
            'rate_per_hour' => $client->office_rate,
            'total_shift_hours' => $totalHours - $totalDeductionsHours,
            'total_duration_hours' => $totalHours,
            'total_break_hours' => $totalBreaks,
            'total_deductions_hours' => $totalDeductionsHours,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'frequency' => $frequency,
        ]);

        foreach ($invoiceItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }


    public function generateSubcontractorInvoice($subcontractorId, $dateFrom, $dateTo, $dueDate, $notes = null)
    {
        $subcontractor = User::findOrFail($subcontractorId);

        // Get all shifts managed by this subcontractor
        $shifts = Shift::with([
            'shiftDates' => function ($q) use ($dateFrom, $dateTo, $subcontractorId) {
                $q->when($subcontractorId, function ($query) use ($subcontractorId) {
                    $query->whereHas('staff.employee', function ($q) use ($subcontractorId) {
                        $q->where('subcontractor', $subcontractorId);
                    });
                });

                $q->whereBetween('shift_date', [$dateFrom, $dateTo]);
            }
        ])->get();



        $invoiceItems = [];
        $totalHours = 0;
        $totalAmount = 0;

        foreach ($shifts as $shift) {
            foreach ($shift->shiftDates as $shiftDate) {
                $hourlyRate = $shiftDate->shift->po_rate ?? 0;

                $item = $this->processShiftDate($shiftDate, $hourlyRate);
                $invoiceItems[] = $item;

                $totalHours += $item['hours'];
                $totalAmount += $item['amount'];
            }
        }

        $invoice = Invoice::create([
            'type' => 'subcontractor',
            'subcontractor_id' => $subcontractorId,
            'issue_date' => now(),
            'due_date' => $dueDate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'notes' => $notes,
            'payment_note' => $subcontractor->payment_terms,
            'rate_per_hour' => $totalHours > 0 ? $totalAmount / $totalHours : 0,
            'total_shift_hours' => $totalHours,
        ]);

        foreach ($invoiceItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }

    public function generateSecurityStaffInvoice($staffId, $site_id, $dateFrom, $dateTo, $dueDate, $notes = null)
    {
        $staff = User::findOrFail($staffId);

        $shiftDatesQuery = ShiftDate::where('staff_id', $staffId)
            ->whereBetween('shift_date', [$dateFrom, $dateTo])
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
            $hourlyRate = $shiftDate->shift->po_rate ?? 0;

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

    protected function processShiftDate($shiftDate, $hourlyRate)
    {
        $date = Carbon::parse($shiftDate->shift_date);
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->start_time);
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->end_time);

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        $breakHours = ($shiftDate->break_time ?? 0) / 60;
        $bookOnHours = $shiftDate->absentee_start_time ?
            Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->absentee_start_time)
            ->diffInMinutes($end) / 60 : 0;
        $bookOffHours = $shiftDate->absentee_end_time ?
            $start->diffInMinutes(Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->absentee_end_time)) / 60 : 0;

        $totalHours = $start->diffInMinutes($end) / 60;
        $payableHours = $totalHours - $breakHours - $bookOnHours - $bookOffHours;
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
            ->whereBetween('shift_date', [$startDate, $endDate]);

        if ($siteId) {
            $shiftDatesQuery->whereHas('shift', fn($q) => $q->where('site_id', $siteId));
        }

        $shiftDates = $shiftDatesQuery->get();

        $totalHours = $totalBreaks = $totalBookOnHours = $totalBookOffHours = 0;

        foreach ($shiftDates as $shiftDate) {
            $date = Carbon::parse($shiftDate->shift_date);

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
                    $requestedHours = $leaveStart->diffInHours($leaveEnd) + 8; // include last day
                    $earnedHoliday = $staff->holiday_balance ?? 0;

                    $paidHours = min($requestedHours, $earnedHoliday);
                    $unpaidHoursLeave = max(0, $requestedHours - $earnedHoliday);

                    $holidayHours += $paidHours;
                    $holidayAmount += $paidHours * $rate;
                    $deductions += $unpaidHoursLeave * $rate;

                    // Update holiday balance
                    $staff->holiday_balance = max(0, $earnedHoliday - $paidHours);
                    $staff->save();
                    break;

                case 'unpaid':
                    $hours = $leaveStart->diffInHours($leaveEnd) + 8;
                    $unpaidHours += $hours;
                    $unpaidAmount += $hours * $rate;
                    $deductions += $hours * $rate;
                    break;

                case 'other':
                    $hours = $leaveStart->diffInHours($leaveEnd) + 8;
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

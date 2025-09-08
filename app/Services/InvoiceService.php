<?php
// app/Services/InvoiceService.php
namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Employee;
use App\Models\ShiftDate;
use App\Models\InvoiceItem;
use App\Models\LeaveRequest;

class InvoiceService
{
    public function generateClientInvoice($clientId, $siteId, $dateFrom, $dateTo, $dueDate, $notes = null)
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

        $shiftDates = ShiftDate::where('staff_id', $staffId)->whereHas('shift', function ($query) use ($site_id) {
            $query->where('site_id', $site_id);
        })->whereBetween('shift_date', [$dateFrom, $dateTo])
            ->with('shift.site')
            ->get();


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

        $breakHours = $shiftDate->break_minutes / 60;
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
            'description' => "Security services at {$shiftDate->shift->site->name} on {$date->format('Y-m-d')}",
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

        // 1️⃣ Calculate total worked hours from shifts
        $shifts = Shift::where('staff_id', $staff->user_id)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->get();

        $totalHours = $totalBreaks = $totalBookOnHours = $totalBookOffHours = 0;

        foreach ($shifts as $shift) {
            $daysAllowed = [];
            if ($shift->days) {
                $shiftDays = json_decode($shift->days, true);
                foreach ($shiftDays as $dayGroup) {
                    $daysAllowed = array_merge($daysAllowed, explode(',', $dayGroup));
                }
            }

            $shiftDates = ShiftDate::where('shift_id', $shift->id)
                ->whereBetween('shift_date', [$startDate, $endDate])
                ->get();

            foreach ($shiftDates as $shiftDate) {
                $date = Carbon::parse($shiftDate->shift_date);
                if (!in_array($date->format('D'), $daysAllowed)) continue;

                $startDT = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->start_time);
                $endDT   = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->end_time);
                if ($endDT->lessThan($startDT)) $endDT->addDay();

                $breakMinutes = $shift->{'break-mins_shift'} ?? 0;
                $durationMinutes = $startDT->diffInMinutes($endDT);

                $totalHours += ($durationMinutes - $breakMinutes) / 60;
                $totalBreaks += $breakMinutes / 60;

                // Absentee hours
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
        }

        $rate = $staff->guard_rate ?? 0;
        $grossAmount = $totalHours * $rate;
        $deductions  = ($totalBookOnHours + $totalBookOffHours) * $rate;

        // 2️⃣ Fetch approved leave requests
        $leaves = LeaveRequest::where('employee_id', $staff->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        $leaveBreakdown = [
            'sick'   => ['paid_days' => 0, 'unpaid_days' => 0, 'amount' => 0],
            'holiday' => ['paid_hours' => 0, 'unpaid_hours' => 0],
            'unpaid' => ['hours' => 0],
            'other'  => ['paid_hours' => 0, 'unpaid_hours' => 0],
        ];

        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse(max($leave->start_date, $startDate));
            $leaveEnd   = Carbon::parse(min($leave->end_date, $endDate));

            switch ($leave->leave_type) {
                case 'sick':
                    $weeklyPay = $staff->weekly_pay ?? 0;
                    $sick = $this->calculateSickPay($staff, $leaveStart, $leaveEnd, $weeklyPay);
                    $deductions += $sick['unpaid_days'] * ($weeklyPay / 5); // 5-day week assumption
                    $leaveBreakdown['sick']['paid_days'] += $sick['paid_days'];
                    $leaveBreakdown['sick']['unpaid_days'] += $sick['unpaid_days'];
                    $leaveBreakdown['sick']['amount'] += $sick['amount'];
                    break;

                case 'holiday':
                    $requestedHours = $leaveStart->diffInHours($leaveEnd) + 8; // include last day
                    $earnedHoliday = $staff->holiday_balance ?? $this->calculateHoliday($staff, $totalHours)['holiday_hours'];

                    $paidHours = min($requestedHours, $earnedHoliday);
                    $unpaidHours = max(0, $requestedHours - $earnedHoliday);
                    $deductions += $unpaidHours * $rate;

                    // 3️⃣ Update holiday balance
                    $staff->holiday_balance = max(0, $earnedHoliday - $paidHours);
                    $staff->save();

                    $leaveBreakdown['holiday']['paid_hours'] += $paidHours;
                    $leaveBreakdown['holiday']['unpaid_hours'] += $unpaidHours;
                    break;

                case 'unpaid':
                    $hours = $leaveStart->diffInHours($leaveEnd) + 8;
                    $deductions += $hours * $rate;
                    $leaveBreakdown['unpaid']['hours'] += $hours;
                    break;

                case 'other':
                    $hours = $leaveStart->diffInHours($leaveEnd) + 8;
                    if ($leave->paid) {
                        $leaveBreakdown['other']['paid_hours'] += $hours;
                    } else {
                        $deductions += $hours * $rate;
                        $leaveBreakdown['other']['unpaid_hours'] += $hours;
                    }
                    break;
            }
        }

        $netAmount = $grossAmount - $deductions;

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
            'leave_breakdown'      => $leaveBreakdown,
            'holiday_balance'      => $staff->holiday_balance,
        ];
    }

    /**
     * Sick Pay (SSP)
     */
    public function calculateSickPay(Employee $staff, Carbon $sickStart, Carbon $sickEnd, int $weeklyPay)
    {
        if ($weeklyPay < 123) {
            return ['eligible' => false, 'paid_days' => 0, 'unpaid_days' => $sickStart->diffInDays($sickEnd) + 1, 'amount' => 0];
        }

        $totalDays = $sickStart->diffInDays($sickEnd) + 1;
        $unpaid = min(3, $totalDays);
        $paid = max(0, $totalDays - 3);
        $paid = min($paid, 28 * 7); // 28 weeks × 7 days

        return [
            'eligible'   => true,
            'total_days' => $totalDays,
            'unpaid_days' => $unpaid,
            'paid_days'  => $paid,
            'amount'     => $paid * 23.75,
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

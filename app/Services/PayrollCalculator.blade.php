<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftDate;

class PayrollCalculator
{
    public function calculatePayroll(Employee $staff, $siteId = null, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $endDate   = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

        $shifts = Shift::where('staff_id', $staff->user_id)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->get();

        $totalHours = 0;
        $totalBreaks = 0;
        $totalBookOnHours = 0;
        $totalBookOffHours = 0;

        foreach ($shifts as $shift) {
            // Allowed days
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

                $startDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->start_time);
                $endDateTime   = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->end_time);

                if ($endDateTime->lessThan($startDateTime)) $endDateTime->addDay();

                $breakMinutes = $shift->{'break-mins_shift'} ?? 0;
                $durationMinutes = $startDateTime->diffInMinutes($endDateTime);

                $totalHours += ($durationMinutes - $breakMinutes) / 60;
                $totalBreaks += $breakMinutes / 60;

                // Absentee
                if ($shiftDate->absentee_start_time) {
                    $absStart = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->absentee_start_time);
                    if ($absStart->between($startDateTime, $endDateTime)) {
                        $totalBookOnHours += $startDateTime->diffInMinutes($absStart) / 60;
                    }
                }

                if ($shiftDate->absentee_end_time) {
                    $absEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->absentee_end_time);
                    if ($absEnd->between($startDateTime, $endDateTime)) {
                        $totalBookOffHours += $absEnd->diffInMinutes($endDateTime) / 60;
                    }
                }
            }
        }

        $rate = $staff->guard_rate ?? 0;
        $grossAmount = $totalHours * $rate;
        $deductions = ($totalBookOnHours + $totalBookOffHours) * $rate;
        $netAmount = $grossAmount - $deductions;

        return [
            'start_date'             => $startDate,
            'end_date'               => $endDate,
            'rate'                   => $rate,
            'total_hours'            => $totalHours,
            'total_breaks'           => $totalBreaks,
            'total_book_on_hours'    => $totalBookOnHours,
            'total_book_off_hours'   => $totalBookOffHours,
            'gross_amount'           => $grossAmount,
            'deductions'             => $deductions,
            'net_amount'             => $netAmount,
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
        $paid = min($paid, 196); // 28 weeks

        return [
            'eligible' => true,
            'total_days' => $totalDays,
            'unpaid_days' => $unpaid,
            'paid_days' => $paid,
            'amount' => $paid * 23.75,
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

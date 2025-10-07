<?php
namespace App\Services;

use App\Models\ShiftDate;
use App\Models\RestrictionOverride;

class ShiftAssignmentService
{
    public static function assignShift($employeeId, $shiftData, $overrideRestrictionType = null)
    {
        // Create or update the shift
        $shift = ShiftDate::updateOrCreate(
            [
                'staff_id' => $employeeId,
                'shift_date' => $shiftData['shift_date']
            ],
            $shiftData
        );

        // If override requested, log it
        if ($overrideRestrictionType) {
            RestrictionOverride::create([
                'user_id' => auth()->id(),
                'entity_id' => $employeeId,
                'entity_type' => \App\Models\Employee::class,
                'restriction_type' => $overrideRestrictionType,
                'reason' => 'Admin override from shift assignment',
            ]);
        }

        return $shift;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\Notification;
use App\Helpers\Notify;
use Carbon\Carbon;

class CheckShiftNotifications extends Command
{
    protected $signature = 'notify:shifts';
    protected $description = 'Send notifications for missed book on/off and unassigned shifts without duplicate alerts';

    public function handle()
    {
        $now = Carbon::now();

        // --- Missed Book On Notifications ---
        $missedBookOns = Shift::whereNotNull('staff_id')
            ->whereNull('book_in_time')
            ->whereDate('from_shift', '<=', $now->toDateString())
            ->whereTime('start_shift', '<=', $now->copy()->subMinutes(15)->format('H:i:s'))
            ->get();

        foreach ($missedBookOns as $shift) {
            $actionUrl = "scheduling?shift_id={$shift->id}";

            if (!$this->notificationExists('Missed Book On', $actionUrl)) {
                $employee = Employee::find($shift->staff_id);
                $guardName = $employee ? "{$employee->first_name} {$employee->last_name}" : 'Unknown';

                Notify::toDashboard(
                    $employee?->id,
                    'alarm',
                    'Missed Book On',
                    "Guard {$guardName} did not book on for their shift starting at {$shift->start_shift} on {$shift->from_shift}.",
                    $actionUrl
                );
            }
        }

        // --- Missed Book Off Notifications ---
        $missedBookOffs = Shift::whereNotNull('staff_id')
            ->whereNull('book_off_time')
            ->whereDate('to_shift', '<=', $now->toDateString())
            ->whereTime('end_shift', '<=', $now->copy()->subMinutes(15)->format('H:i:s'))
            ->get();

        foreach ($missedBookOffs as $shift) {
            $actionUrl = "scheduling?shift_id={$shift->id}";

            if (!$this->notificationExists('Missed Book Off', $actionUrl)) {
                $employee = Employee::find($shift->staff_id);
                $guardName = $employee ? "{$employee->first_name} {$employee->last_name}" : 'Unknown';

                Notify::toDashboard(
                    $employee?->id,
                    'alarm',
                    'Missed Book Off',
                    "Guard {$guardName} did not book off for their shift ending at {$shift->end_shift} on {$shift->to_shift}.",
                    $actionUrl
                );
            }
        }

        // --- Unassigned Shift Starting Soon ---
        $unassignedShifts = Shift::whereNull('staff_id')
            ->whereDate('from_shift', '=', $now->toDateString())
            ->whereTime('start_shift', '>=', $now->format('H:i:s'))
            ->whereTime('start_shift', '<=', $now->copy()->addHour()->format('H:i:s'))
            ->get();

        foreach ($unassignedShifts as $shift) {
            $actionUrl = "scheduling?shift_id={$shift->id}";

            if (!$this->notificationExists('Unassigned Shift', $actionUrl)) {
                Notify::toDashboard(
                    null,
                    'alarm',
                    'Unassigned Shift',
                    "A shift at {$shift->start_shift} on {$shift->from_shift} is starting soon and no guard has been assigned.",
                    $actionUrl
                );
            }
        }

        $this->info('Shift notifications processed successfully.');
    }

    /**
     * Check if a notification with same title & action_url already exists
     */
    protected function notificationExists($title, $actionUrl)
    {
        return Notification::where('title', $title)
            ->where('action_url', $actionUrl)
            ->exists();
    }
}

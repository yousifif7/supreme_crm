<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;
use App\Models\Employee;
use function App\Helpers\send_push_notification;
use Carbon\Carbon;
use Notify;

class ProcessShiftNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:process-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send shift notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // --- Missed Book On ---
        $missedBookOns = Shift::whereNotNull('staff_id')
            ->whereNull('book_in_time')
            ->whereDate('from_shift', '<=', $now->toDateString())
            ->whereTime('start_shift', '<=', $now->copy()->subMinutes(15)->format('H:i:s'))
            ->where('missed_book_on_notified', false)
            ->get();

        foreach ($missedBookOns as $shift) {
            $employee = Employee::find($shift->staff_id);
            $guardName = $employee ? "{$employee->first_name} {$employee->last_name}" : 'Unknown';

            Notify::toDashboard(
                null,
                'alarm',
                'Missed Book On',
                "Guard {$guardName} did not book on for shift No. {$shift->id} at {$shift->start_shift} on {$shift->from_shift}.",
                "scheduling?{$shift->id}"
            );

            if ($employee) {
                send_push_notification(
                    $employee->id,
                    'Shift Missed',
                    'Your shift has started, and you did not book on.',
                    ['shift_id' => $shift->id]
                );
            }

            $shift->update(['missed_book_on_notified' => true]);
        }

        // --- Missed Book Off ---
        $missedBookOffs = Shift::whereNotNull('staff_id')
            ->whereNull('book_off_time')
            ->whereDate('to_shift', '<=', $now->toDateString())
            ->whereTime('end_shift', '<=', $now->copy()->subMinutes(15)->format('H:i:s'))
            ->where('missed_book_off_notified', false)
            ->get();

        foreach ($missedBookOffs as $shift) {
            $employee = Employee::find($shift->staff_id);
            $guardName = $employee ? "{$employee->first_name} {$employee->last_name}" : 'Unknown';

            Notify::toDashboard(
                null,
                'alarm',
                'Missed Book Off',
                "Guard {$guardName} did not book off for shift No. {$shift->id} at {$shift->end_shift} on {$shift->to_shift}.",
                "scheduling?{$shift->id}"
            );

            if ($employee) {
                send_push_notification(
                    $employee->id,
                    'Shift Finished',
                    'Your shift ended, and you did not book off.',
                    ['shift_id' => $shift->id]
                );
            }

            $shift->update(['missed_book_off_notified' => true]);
        }

        // --- Unassigned Shifts ---
        $unassignedShifts = Shift::whereNull('staff_id')
            ->whereDate('from_shift', '=', $now->toDateString())
            ->whereTime('start_shift', '>=', $now->format('H:i:s'))
            ->whereTime('start_shift', '<=', $now->copy()->addHour()->format('H:i:s'))
            ->where('unassigned_shift_notified', false)
            ->get();

        foreach ($unassignedShifts as $shift) {
            Notify::toDashboard(
                null,
                'alarm',
                'Unassigned Shift',
                "Shift {$shift->id} at {$shift->start_shift} on {$shift->from_shift} is starting soon and is unassigned.",
                "scheduling?{$shift->id}"
            );

            $shift->update(['unassigned_shift_notified' => true]);
        }

        $this->info('✅ Shift notifications processed successfully.');
    }
}

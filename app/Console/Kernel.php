<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        \App\Console\Commands\ProcessShiftNotifications::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Example: schedule your custom command here
        $schedule->command('shifts:process-notifications')->everyFiveMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

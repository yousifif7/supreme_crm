<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        \App\Console\Commands\ProcessShiftNotifications::class,
        \App\Console\Commands\TestSiaRequest::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Schedule the shift notification command to run every 15 minutes.
        // Use withoutOverlapping to avoid concurrent runs.
        $schedule->command('shifts:process-notifications')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();
        
        // Cleanup stuck connections every 5 minutes
        $schedule->command('db:cleanup-connections')
            ->everyFiveMinutes()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

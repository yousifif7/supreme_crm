<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        \App\Console\Commands\CheckSiaLicences::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Run SIA licence check once daily at 16:00 UK time (handles GMT/BST)
        $schedule->command('sia:check')
            ->dailyAt('16:00')
            ->timezone('Europe/London')
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

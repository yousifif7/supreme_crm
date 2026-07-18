<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Legacy Kernel kept for command auto-loading.
 * All schedule definitions live in routes/console.php (Laravel 11/12).
 */
class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\CheckSiaLicences::class,
        \App\Console\Commands\ProcessShiftNotifications::class,
        \App\Console\Commands\NotifyAdminBeforeDocumentExpiry::class,
        \App\Console\Commands\RevokeIdleLoginSessions::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Intentionally empty — schedules are registered in routes/console.php
        // to avoid duplicate SIA / notification runs on Hostinger.
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}

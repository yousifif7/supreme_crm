<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessShiftNotifications extends Command
{
    protected $signature = 'shifts:process-notifications';

    protected $description = 'Process and send shift notifications';

    public function handle()
    {
        Log::info('Notification command started');

        // Delegate entirely to the web controller which contains all logic
        try {
            $controller = new \App\Http\Controllers\ShiftNotificationController();
            $controller->process(new \Illuminate\Http\Request());
        } catch (\Throwable $e) {
            Log::error('ShiftNotificationController::process failed: ' . $e->getMessage());
        }

        Log::info('Notification command ended');

        // Close any lingering database connections
        \DB::disconnect();

        $this->info('Shift notifications processed successfully.');
    }
}

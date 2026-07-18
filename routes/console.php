<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Hostinger / production cron (run every minute)
|--------------------------------------------------------------------------
| Add ONE cron job in hPanel → Advanced → Cron Jobs:
|
|   * * * * *  cd /home/USER/domains/YOURDOMAIN/public_html && php artisan schedule:run >> /dev/null 2>&1
|
| Adjust the path to your Laravel root (where artisan lives). Laravel then
| runs the tasks below on their own cadences.
*/

// Shift / patrol / check-call notifications — every minute
Schedule::command('shifts:process-notifications')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Document expiry reminders (60 / 30 / 0 days) — once daily UK morning
Schedule::command('notify:document-expiry')
    ->dailyAt('09:00')
    ->timezone('Europe/London')
    ->withoutOverlapping();

// SIA licence check — daily UK mid-morning
Schedule::command('sia:check')
    ->dailyAt('10:30')
    ->timezone('Europe/London')
    ->withoutOverlapping();

// Auto-logout idle CRM sessions — every 5 minutes
Schedule::command('auth:revoke-idle-sessions --minutes=30')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



// Shift notifications — every minute, skip if previous run still going
Schedule::command('shifts:process-notifications')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();



// SIA licence check — daily at 11:00 UK time (handles GMT/BST)
Schedule::command('sia:check')
    ->dailyAt('10:30')
    ->timezone('Europe/London')
    ->withoutOverlapping();


<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('notify:document-expiry')->everyMinute();
Schedule::command('shifts:process-notifications')->everyMinute();
Schedule::command('notify:shifts')->everyMinute();

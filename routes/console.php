<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('fees:generate-monthly')->monthlyOn(1, '00:01');
Schedule::command('saas:run-dunning')->dailyAt('00:05');
Schedule::command('fees:send-reminders')->dailyAt('09:00');

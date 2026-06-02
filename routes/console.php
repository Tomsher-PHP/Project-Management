<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('task:notify-start')->everyMinute()->withoutOverlapping();
Schedule::command('projects:recalculate-times')->everySixHours()->withoutOverlapping();

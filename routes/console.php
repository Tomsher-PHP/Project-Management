<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('task:notify-start')->everyMinute()->withoutOverlapping();
Schedule::command('meeting:notify-reminder')->everyMinute()->withoutOverlapping();

// Do not need this recalculation for now
// Schedule::command('projects:recalculate-times')->everySixHours()->withoutOverlapping();

Schedule::command('tasks:generate-scheduled')->hourly()->withoutOverlapping();
Schedule::command('notify:check-daily-shift-hours')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();

Schedule::command('project:timeline-ending-soon-notifications')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('project:hosting-expiry-notifications')->dailyAt('08:00')->withoutOverlapping();


Schedule::command('app:generate-daily-work-reports')->everyMinute()->withoutOverlapping();

Schedule::command('app:generate-project-reports')->everyMinute()->withoutOverlapping();

Artisan::command('data-sync:recover-outbound-deliveries', function (\App\Services\DataSync\OutboundDataSyncService $service) {
    $this->info('Starting recovery of abandoned/interrupted outbound data sync deliveries...');
    $recovered = $service->recoverPendingDeliveries();
    $this->info("Recovered and re-dispatched {$recovered} pending delivery/deliveries.");
})->purpose('Recover and re-dispatch abandoned pending outbound data sync deliveries');

Schedule::command('data-sync:recover-outbound-deliveries')->everyFiveMinutes()->withoutOverlapping();

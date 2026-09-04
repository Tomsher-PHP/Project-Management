<?php

namespace App\Console\Commands;

use App\Services\DailyShiftHoursService;
use Illuminate\Console\Command;

class CheckDailyShiftHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:check-daily-shift-hours';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check daily shift hours for active employees and send notifications for unfulfilled shift hours.';

    /**
     * Execute the console command.
     */
    public function handle(DailyShiftHoursService $service): int
    {
        $this->info('Starting daily shift hours check...');

        $summary = $service->processDailyShiftHoursCheck();

        $this->info("Daily shift hours check completed. Evaluated {$summary['checked_count']} employee/date pairs, queued notifications for {$summary['sent_count']} short-hours instances.");

        return Command::SUCCESS;
    }
}

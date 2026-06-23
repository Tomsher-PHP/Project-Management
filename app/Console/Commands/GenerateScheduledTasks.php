<?php

namespace App\Console\Commands;

use App\Services\Task\GenerateScheduleTaskService;
use Illuminate\Console\Command;

class GenerateScheduledTasks extends Command
{
    protected $signature = 'tasks:generate-scheduled {date? : Date to generate in Y-m-d format}';

    protected $description = 'Generate normal tasks for eligible recurring task schedules.';

    public function handle(GenerateScheduleTaskService $service): int
    {
        $generatedCount = $service->generateForDate($this->argument('date'));

        $this->info("Generated {$generatedCount} scheduled task(s).");

        return self::SUCCESS;
    }
}

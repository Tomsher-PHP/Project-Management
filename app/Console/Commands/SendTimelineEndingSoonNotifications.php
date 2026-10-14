<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProjectNotificationService;

class SendTimelineEndingSoonNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:timeline-ending-soon-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for project timelines that are ending soon based on global settings.';

    /**
     * Execute the console command.
     */
    public function handle(ProjectNotificationService $service)
    {
        $this->info('Starting timeline ending soon notification process...');
        
        $service->processTimelineEndingSoon();
        
        $this->info('Completed timeline ending soon notification process.');
    }
}

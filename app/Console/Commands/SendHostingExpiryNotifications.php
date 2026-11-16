<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProjectNotificationService;

class SendHostingExpiryNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:hosting-expiry-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for project hosting records that are expiring soon or expired based on global settings.';

    /**
     * Execute the console command.
     */
    public function handle(ProjectNotificationService $service)
    {
        $this->info('Starting hosting expiry notification process...');

        $service->processHostingExpiryNotifications();

        $this->info('Completed hosting expiry notification process.');
    }
}

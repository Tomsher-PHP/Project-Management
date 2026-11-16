<?php

namespace App\Jobs;

use App\Services\BroadcastEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchBroadcastEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $broadcastId;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [10, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(int $broadcastId)
    {
        $this->broadcastId = $broadcastId;
    }

    /**
     * Execute the job.
     */
    public function handle(BroadcastEmailService $broadcastEmailService): void
    {
        $broadcastEmailService->dispatchBroadcast($this->broadcastId);
    }
}


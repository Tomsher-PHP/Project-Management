<?php

namespace App\Jobs;

use App\Models\BroadcastEmailDelivery;
use App\Services\BroadcastEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendBroadcastEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $broadcastId;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [30, 120];

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
        $broadcastEmailService->sendBroadcast($this->broadcastId);
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        BroadcastEmailDelivery::query()
            ->where('broadcast_id', $this->broadcastId)
            ->whereIn('status', [
                BroadcastEmailDelivery::STATUS_PENDING,
                BroadcastEmailDelivery::STATUS_PROCESSING,
            ])
            ->update([
                'status' => BroadcastEmailDelivery::STATUS_FAILED,
                'error_message' => Str::limit($exception?->getMessage() ?? 'Job failed after exhausting retries.', 1000),
            ]);
    }
}


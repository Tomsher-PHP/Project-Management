<?php

namespace App\Jobs\DataSync;

use App\Models\DataSyncOutboundHistory;
use App\Services\DataSync\OutboundHttpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOutboundApiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $historyId)
    {
        $this->afterCommit = true;
        $this->tries = (int) config('data_sync_outbound.retry.max_attempts', 3);
        $this->backoff = (array) config('data_sync_outbound.retry.backoff', [10, 30, 60]);
    }

    /**
     * Execute the job.
     */
    public function handle(OutboundHttpService $httpService): void
    {
        /** @var DataSyncOutboundHistory|null $history */
        $history = DataSyncOutboundHistory::query()
            ->with(['outboundApi.connection'])
            ->find($this->historyId);

        if (! $history) {
            Log::warning("SendOutboundApiJob: Outbound history record [{$this->historyId}] not found.");

            return;
        }

        // Prevent duplicate processing if the delivery already succeeded
        if ($history->status === DataSyncOutboundHistory::STATUS_SUCCESS) {
            return;
        }

        $api = $history->outboundApi;
        $connection = $api?->connection;

        // Verify connection and API remain active and available
        if (! $api || ! $api->is_active || ! $connection || ! $connection->is_active || $connection->trashed()) {
            $history->update([
                'status' => DataSyncOutboundHistory::STATUS_FAILED,
                'error_message' => 'Delivery aborted: Outbound connection or API configuration is inactive or deleted.',
            ]);

            return;
        }

        $result = $httpService->send($history);

        if ($result['success']) {
            $history->update([
                'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
                'response_code' => $result['status_code'],
                'response_body' => $result['body'],
                'error_message' => null,
            ]);

            return;
        }

        // Determine if error is eligible for automatic retry
        $isRetryable = $result['is_retryable'];
        $canRetry = $isRetryable && ($this->attempts() < $this->tries);

        if ($canRetry) {
            // Keep status as pending during retry attempts until exhausted or succeeded
            $history->update([
                'status' => DataSyncOutboundHistory::STATUS_PENDING,
                'response_code' => $result['status_code'],
                'response_body' => $result['body'],
                'error_message' => $result['error'],
            ]);

            $attemptIndex = max(0, $this->attempts() - 1);
            $delay = $this->backoff[$attemptIndex] ?? end($this->backoff) ?? 10;

            $this->release($delay);

            return;
        }

        // Mark permanently failed when retries are exhausted or failure is non-retryable
        $history->update([
            'status' => DataSyncOutboundHistory::STATUS_FAILED,
            'response_code' => $result['status_code'],
            'response_body' => $result['body'],
            'error_message' => $result['error'],
        ]);
    }

    /**
     * Handle a job failure after exhausting all attempts or on fatal exception.
     */
    public function failed(?Throwable $exception): void
    {
        try {
            $history = DataSyncOutboundHistory::find($this->historyId);
            if ($history && $history->status !== DataSyncOutboundHistory::STATUS_SUCCESS) {
                $history->update([
                    'status' => DataSyncOutboundHistory::STATUS_FAILED,
                    'error_message' => $exception ? $exception->getMessage() : ($history->error_message ?? 'Job failed after exhausting retries.'),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('SendOutboundApiJob failed() handler error', [
                'history_id' => $this->historyId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


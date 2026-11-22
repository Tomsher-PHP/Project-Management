<?php

namespace App\Jobs;

use App\Mail\ProjectReportMail;
use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendProjectReportDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public int $deliveryId
    ) {
    }

    public function handle(): void
    {
        $delivery = DailyWorkReportDelivery::with(
            'reportLog'
        )->findOrFail($this->deliveryId);

        // Prevent already-sent deliveries from being sent again.
        if ($delivery->status === 'sent') {
            return;
        }

        $delivery->increment('attempt_count');

        $reportLog = $delivery->reportLog;

        try {
            if (! $reportLog) {
                throw new RuntimeException('Project report log not found.');
            }

            if (blank($delivery->recipient_email)) {
                throw new RuntimeException('Delivery recipient email is missing.');
            }

            if (empty($reportLog->report_data)) {
                throw new RuntimeException('Report data snapshot not found in report log.');
            }

            // Send Project Report email with HTML table directly in body using the report snapshot.
            Mail::to($delivery->recipient_email)
                ->send(new ProjectReportMail($reportLog));

            $delivery->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_error' => null,
            ]);

            $this->refreshReportStatus($reportLog->id);

        } catch (Throwable $exception) {
            // Keep the delivery queued while Laravel has retries remaining.
            $delivery->update([
                'status' => 'queued',
                'last_error' => $this->formatError($exception),
            ]);

            $this->refreshReportStatus($reportLog?->id);

            throw $exception;
        }
    }

    /**
     * Mark the delivery as failed after all queue attempts are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $delivery = DailyWorkReportDelivery::with(
            'reportLog'
        )->find($this->deliveryId);

        if (! $delivery || $delivery->status === 'sent') {
            return;
        }

        $reportLogId = $delivery->reportLog?->id;

        $delivery->update([
            'status' => 'failed',
            'last_error' => $exception
                ? $this->formatError($exception)
                : $delivery->last_error,
        ]);

        $this->refreshReportStatus($reportLogId);
    }

    /**
     * Update the overall report log status based on all individual deliveries.
     */
    public function refreshReportStatus(?int $reportLogId): void
    {
        if (! $reportLogId) {
            return;
        }

        DB::transaction(function () use ($reportLogId) {
            $reportLog = DailyWorkReportLog::query()
                ->lockForUpdate()
                ->find($reportLogId);

            if (! $reportLog) {
                return;
            }

            $total = $reportLog->deliveries()->count();

            $sent = $reportLog->deliveries()
                ->where('status', 'sent')
                ->count();

            $failed = $reportLog->deliveries()
                ->where('status', 'failed')
                ->count();

            $status = 'queued';
            $completedAt = null;

            if ($total > 0 && $sent === $total) {
                $status = 'sent';
                $completedAt = now();
            } elseif ($total > 0 && ($sent + $failed) === $total) {
                $status = $sent > 0 ? 'partial' : 'failed';
                $completedAt = now();
            }

            $reportLog->update([
                'status' => $status,
                'completed_at' => $completedAt,
            ]);
        });
    }

    /**
     * Format the exception message for database storage.
     */
    private function formatError(Throwable $exception): string
    {
        return mb_substr($exception->getMessage(), 0, 5000);
    }
}


<?php

namespace App\Jobs;

use App\Mail\DailyWorkReportMail;
use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendDailyWorkReportDelivery implements ShouldQueue
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
            'reportLog.user'
        )->findOrFail($this->deliveryId);

        if ($delivery->status === 'sent') {
            return;
        }

        $delivery->increment('attempt_count');

        $reportLog = $delivery->reportLog;

        try {
            if (! $reportLog) {
                throw new RuntimeException('Daily work report log not found.');
            }

            if (blank($delivery->recipient_email)) {
                throw new RuntimeException('Delivery recipient email is missing.');
            }

            // The report log contains the complete individual or consolidated report.
            Mail::to($delivery->recipient_email)
                ->send(new DailyWorkReportMail($reportLog));

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
     * Update the overall report status based on all deliveries.
     */
    private function refreshReportStatus(?int $reportLogId): void
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

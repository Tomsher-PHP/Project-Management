<?php

namespace App\Jobs;

use App\Mail\DailyWorkReportMail;
use App\Models\DailyWorkReportDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDailyWorkReportDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $deliveryId
    ) {
    }

    public function handle(): void
    {
        $delivery = DailyWorkReportDelivery::with(
            'reportLog.user',
            'reportLog.deliveries'
        )->findOrFail($this->deliveryId);

        if ($delivery->status === 'sent') {
            return;
        }

        $delivery->increment('attempt_count');

        try {
            $reportLog = $delivery->reportLog;

            // The report log contains the complete report data.
            // This can be an individual or consolidated report.
            Mail::to($delivery->recipient_email)
                ->send(new DailyWorkReportMail($reportLog));

            $delivery->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_error' => null,
            ]);

            $this->refreshReportStatus($reportLog->fresh());

        } catch (Throwable $exception) {

            $delivery->update([
                'status' => 'failed',
                'last_error' => mb_substr(
                    $exception->getMessage(),
                    0,
                    60000
                ),
            ]);

            $this->refreshReportStatus(
                $delivery->reportLog->fresh()
            );

            throw $exception;
        }
    }

    /**
     * Update the overall report status based on all deliveries.
     */
    private function refreshReportStatus($reportLog): void
    {
        $deliveries = $reportLog->deliveries()->get();

        $total = $deliveries->count();

        $sent = $deliveries->where('status', 'sent')->count();

        $failed = $deliveries->where('status', 'failed')->count();

        // Anything not sent or failed is still pending/queued.
        $pending = $total - $sent - $failed;

        $status = 'queued';
        $completedAt = null;

        if ($total > 0 && $sent === $total) {
            $status = 'sent';
            $completedAt = now();

        } elseif ($total > 0 && $sent + $failed === $total) {

            $status = $sent > 0 ? 'partial' : 'failed';
            $completedAt = now();
        }

        $reportLog->update([
            'status' => $status,
            'completed_at' => $completedAt,
        ]);
    }
}

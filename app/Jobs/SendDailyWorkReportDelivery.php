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

            $this->refreshReportStatus($delivery->reportLog->fresh());

            throw $exception;
        }
    }

    private function refreshReportStatus($reportLog): void
    {
        $deliveries = $reportLog->deliveries()->get();

        $sent = $deliveries->where('status', 'sent')->count();
        $failed = $deliveries->where('status', 'failed')->count();
        $total = $deliveries->count();

        $status = 'queued';

        if ($total > 0 && $sent === $total) {
            $status = 'sent';
        } elseif ($failed > 0 && $sent > 0) {
            $status = 'partial';
        } elseif ($failed > 0 && $sent === 0) {
            $status = 'failed';
        }

        $reportLog->update([
            'status' => $status,
            'completed_at' => in_array($status, ['sent', 'partial', 'failed'], true)
                ? now()
                : null,
        ]);
    }
}
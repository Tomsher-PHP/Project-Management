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

            if (empty($reportLog->report_data)) {
                throw new RuntimeException('Report data snapshot not found in report log.');
            }

            // Resolve TO and CC recipients from log snapshot or delivery sources
            $toRecipients = data_get($reportLog->recipients_snapshot, 'to', []);
            $ccRecipients = data_get($reportLog->recipients_snapshot, 'cc', []);

            if (empty($toRecipients)) {
                $sourcesTo = data_get($delivery->recipient_sources, 'to', []);
                $toRecipients = ! empty($sourcesTo) ? $sourcesTo : [$delivery->recipient_email];
            }
            if (empty($ccRecipients)) {
                $ccRecipients = data_get($delivery->recipient_sources, 'cc', []);
            }

            $toEmails = collect($toRecipients)
                ->map(fn($r) => is_array($r) ? ($r['email'] ?? null) : (string) $r)
                ->filter()
                ->map(fn($e) => mb_strtolower(trim((string) $e)))
                ->unique()
                ->values()
                ->all();

            $ccEmails = collect($ccRecipients)
                ->map(fn($r) => is_array($r) ? ($r['email'] ?? null) : (string) $r)
                ->filter()
                ->map(fn($e) => mb_strtolower(trim((string) $e)))
                ->unique()
                ->reject(fn($email) => in_array($email, $toEmails, true))
                ->values()
                ->all();

            if (empty($toEmails)) {
                $fallbackTo = config('mail.from.address');
                if (blank($fallbackTo)) {
                    throw new RuntimeException('Delivery recipient email is missing.');
                }
                $toEmails = [mb_strtolower(trim((string) $fallbackTo))];
                $ccEmails = array_values(array_diff($ccEmails, $toEmails));
            }

            // Send Project Report email with HTML table directly in body using the report snapshot.
            $mail = Mail::to($toEmails);

            if (! empty($ccEmails)) {
                $mail->cc($ccEmails);
            }

            $mail->send(new ProjectReportMail($reportLog));

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


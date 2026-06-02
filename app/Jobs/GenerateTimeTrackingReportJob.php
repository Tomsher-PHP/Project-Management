<?php

namespace App\Jobs;

use App\Models\GeneratedReport;
use App\Services\Reports\TimeTrackingReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateTimeTrackingReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $generatedReportId)
    {
        $this->afterCommit();
        $this->onQueue('reports');
    }

    public function handle(TimeTrackingReportService $timeTrackingReportService): void
    {
        $generatedReport = GeneratedReport::query()->find($this->generatedReportId);

        if (! $generatedReport || $generatedReport->status === GeneratedReport::STATUS_COMPLETED) {
            return;
        }

        $generatedReport->forceFill([
            'status' => GeneratedReport::STATUS_PROCESSING,
            'processing_started_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ])->save();

        try {
            $timeTrackingReportService->generateQueuedExport($generatedReport);
        } catch (Throwable $exception) {
            $generatedReport->forceFill([
                'status' => GeneratedReport::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => mb_substr($exception->getMessage(), 0, 65535),
            ])->save();

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $generatedReport = GeneratedReport::query()->find($this->generatedReportId);

        if (! $generatedReport) {
            return;
        }

        $generatedReport->forceFill([
            'status' => GeneratedReport::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $exception
                ? mb_substr($exception->getMessage(), 0, 65535)
                : $generatedReport->error_message,
        ])->save();
    }
}

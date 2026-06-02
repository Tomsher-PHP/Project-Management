<?php

namespace App\Console\Commands;

use App\Models\GeneratedReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportsCleanupCommand extends Command
{
    protected $signature = 'reports:cleanup';

    protected $description = 'Expire all completed generated report files and clean up stored files safely.';

    public function handle(): int
    {
        $processed = 0;
        $expired = 0;
        $orphaned = 0;

        $this->info('Starting generated report cleanup...');

        GeneratedReport::query()
            ->where('status', GeneratedReport::STATUS_COMPLETED)
            ->orderBy('id')
            ->chunkById(100, function ($reports) use (&$processed, &$expired, &$orphaned) {
                foreach ($reports as $report) {
                    $processed++;
                    $result = $this->cleanupReport($report);

                    if ($result === GeneratedReport::STATUS_EXPIRED) {
                        $expired++;
                    }

                    if ($result === GeneratedReport::STATUS_ORPHANED) {
                        $orphaned++;
                    }
                }
            });

        $this->info("Processed: {$processed}");
        $this->info("Expired: {$expired}");
        $this->info("Orphaned: {$orphaned}");
        $this->info('Generated report cleanup complete.');

        return self::SUCCESS;
    }

    protected function cleanupReport(GeneratedReport $report): string
    {
        if (blank($report->disk) || blank($report->path)) {
            $this->markAsOrphaned($report, 'Generated report is missing disk or path metadata.');

            return GeneratedReport::STATUS_ORPHANED;
        }

        try {
            $exists = Storage::disk($report->disk)->exists($report->path);
        } catch (Throwable $exception) {
            $this->markAsOrphaned(
                $report,
                'Failed to verify generated report file existence: ' . $exception->getMessage(),
                $exception
            );

            return GeneratedReport::STATUS_ORPHANED;
        }

        if (! $exists) {
            $this->markAsOrphaned($report, 'Generated report file does not exist on storage.');

            return GeneratedReport::STATUS_ORPHANED;
        }

        try {
            $deleted = Storage::disk($report->disk)->delete($report->path);
        } catch (Throwable $exception) {
            $this->markAsOrphaned(
                $report,
                'Failed to delete generated report file: ' . $exception->getMessage(),
                $exception
            );

            return GeneratedReport::STATUS_ORPHANED;
        }

        if (! $deleted) {
            $this->markAsOrphaned($report, 'Storage delete returned false for generated report file.');

            return GeneratedReport::STATUS_ORPHANED;
        }

        $report->forceFill([
            'status' => GeneratedReport::STATUS_EXPIRED,
            'updated_at' => now(),
        ])->save();

        $this->line("Expired report #{$report->id} ({$report->filename}).");

        return GeneratedReport::STATUS_EXPIRED;
    }

    protected function markAsOrphaned(GeneratedReport $report, string $message, ?Throwable $exception = null): void
    {
        $report->forceFill([
            'status' => GeneratedReport::STATUS_ORPHANED,
            'error_message' => mb_substr($message, 0, 65535),
            'updated_at' => now(),
        ])->save();

        Log::warning('Generated report cleanup issue', [
            'generated_report_id' => $report->id,
            'disk' => $report->disk,
            'path' => $report->path,
            'message' => $message,
            'exception' => $exception?->getMessage(),
        ]);

        $this->warn("Marked report #{$report->id} as orphaned.");
    }
}

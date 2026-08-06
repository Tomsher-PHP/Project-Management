<?php

namespace App\Jobs;

use App\Models\AppraisalQuestionImport;
use App\Services\AppraisalSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportAppraisalQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public readonly int $importId) {}

    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping((string) $this->importId),
        ];
    }

    public function handle(AppraisalSettingsService $service): void
    {
        $import = AppraisalQuestionImport::find($this->importId);

        if (! $import) {
            Log::warning('Appraisal question import job skipped: import record not found.', ['import_id' => $this->importId]);
            return;
        }

        if (in_array($import->status, [AppraisalQuestionImport::STATUS_COMPLETED, AppraisalQuestionImport::STATUS_COMPLETED_WITH_ERRORS], true)) {
            Log::info('Appraisal question import job skipped: already processed.', ['import_id' => $this->importId]);
            return;
        }

        $service->processQuestionImport($import->id);
        $service->deleteQuestionImportFile($import->id);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Appraisal question import job failed permanently.', [
            'import_id' => $this->importId,
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);

        $service = app(AppraisalSettingsService::class);
        $service->markImportFailed($this->importId, $exception?->getMessage() ?? 'Import failed unexpectedly.');
        $service->deleteQuestionImportFile($this->importId);
    }
}

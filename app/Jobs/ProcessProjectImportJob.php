<?php

namespace App\Jobs;

use App\Models\ProjectImport;
use App\Services\ProjectImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessProjectImportJob implements ShouldQueue
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

    /**
     * Execute the common project import job.
     */
    public function handle(ProjectImportService $importService): void
    {
        $import = ProjectImport::find($this->importId);

        if (!$import) {
            Log::warning('ProcessProjectImportJob skipped: import record not found.', ['import_id' => $this->importId]);
            return;
        }

        // Prevent duplicate execution / race condition
        if (in_array($import->status, [ProjectImport::STATUS_COMPLETED, ProjectImport::STATUS_COMPLETED_WITH_ERRORS], true)) {
            Log::info('ProcessProjectImportJob skipped: import already completed.', ['import_id' => $this->importId]);
            return;
        }

        try {
            $handler = $importService->resolveImportHandler($import->module_key);
        } catch (Throwable $e) {
            Log::error('ProcessProjectImportJob failed to resolve handler', [
                'import_id' => $this->importId,
                'module_key' => $import->module_key,
                'error' => $e->getMessage(),
            ]);
            $importService->markImportFailed($this->importId, $e->getMessage());
            $importService->deleteImportFile($this->importId);
            return;
        }

        $import->update([
            'status' => ProjectImport::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        try {
            $handler->process($import);
        } catch (Throwable $e) {
            Log::error('ProcessProjectImportJob handler exception', [
                'import_id' => $this->importId,
                'module_key' => $import->module_key,
                'error' => $e->getMessage(),
            ]);
            $importService->markImportFailed($this->importId, $e->getMessage());
            throw $e;
        } finally {
            $importService->deleteImportFile($this->importId);
        }
    }

    /**
     * Handle permanent job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('ProcessProjectImportJob failed permanently.', [
            'import_id' => $this->importId,
            'error' => $exception?->getMessage(),
        ]);

        $importService = app(ProjectImportService::class);
        $importService->markImportFailed($this->importId, $exception?->getMessage() ?? 'Import failed unexpectedly.');
        $importService->deleteImportFile($this->importId);
    }
}

<?php

namespace App\Jobs;

use App\Models\ProjectImport;
use App\Services\ProjectImportService;
use App\Services\ProjectImportTrackingManager;
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

    /**
     * Set tries to 1 to disable automatic replay of partially processed imports.
     * Prevents potential duplicate insertions if an import is interrupted.
     */
    public int $tries = 1;

    public int $timeout = 600;

    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function __construct(public readonly string|int $importId) {}

    public function middleware(): array
    {
        return [
            new WithoutOverlapping((string) $this->importId),
        ];
    }

    /**
     * Execute the project import job.
     *
     * @param  \App\Services\ProjectImportService  $importService
     * @param  \App\Services\ProjectImportTrackingManager  $trackingManager
     * @return void
     */
    public function handle(
        ProjectImportService $importService,
        ProjectImportTrackingManager $trackingManager
    ): void {
        $importIdStr = (string) $this->importId;

        // 1. Check non-database temporary tracking first
        $tracking = $trackingManager->getTracking($importIdStr);

        if ($tracking) {
            $this->processTrackedImport($tracking, $importService, $trackingManager);
            return;
        }

        // 2. Legacy fallback for database-backed import record
        $this->processLegacyImport($importService);
    }

    /**
     * Process a non-database tracked import.
     */
    protected function processTrackedImport(
        array $tracking,
        ProjectImportService $importService,
        ProjectImportTrackingManager $trackingManager
    ): void {
        $importIdStr = (string) $this->importId;

        // Prevent duplicate execution / race condition
        if (in_array($tracking['status'], [
            ProjectImportTrackingManager::STATUS_COMPLETED,
            ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS,
            ProjectImportTrackingManager::STATUS_FAILED,
        ], true)) {
            Log::info('ProcessProjectImportJob skipped: tracked import already finished.', ['import_id' => $importIdStr]);
            return;
        }

        try {
            $handler = $importService->resolveImportHandler($tracking['module_key']);
        } catch (Throwable $e) {
            Log::error('ProcessProjectImportJob failed to resolve handler', [
                'import_id' => $importIdStr,
                'module_key' => $tracking['module_key'],
                'error' => $e->getMessage(),
            ]);
            $trackingManager->updateTracking($importIdStr, [
                'status' => ProjectImportTrackingManager::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now()->toIso8601String(),
            ]);
            $trackingManager->deleteSourceFile($importIdStr);
            return;
        }

        try {
            $handler->process($importIdStr);
        } catch (Throwable $e) {
            Log::error('ProcessProjectImportJob handler exception', [
                'import_id' => $importIdStr,
                'module_key' => $tracking['module_key'],
                'error' => $e->getMessage(),
            ]);
            $trackingManager->markInterrupted($importIdStr, $e->getMessage());
            throw $e;
        } finally {
            $trackingManager->deleteSourceFile($importIdStr);
        }
    }

    /**
     * Legacy execution for existing ProjectImport database model.
     */
    protected function processLegacyImport(ProjectImportService $importService): void
    {
        $import = ProjectImport::find($this->importId);

        if (!$import) {
            Log::warning('ProcessProjectImportJob skipped: import record not found.', ['import_id' => $this->importId]);
            return;
        }

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
            $importService->markImportFailed((int) $this->importId, $e->getMessage());
            $importService->deleteImportFile((int) $this->importId);
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
            $importService->markImportFailed((int) $this->importId, $e->getMessage());
            throw $e;
        } finally {
            $importService->deleteImportFile((int) $this->importId);
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

        $importIdStr = (string) $this->importId;
        $trackingManager = app(ProjectImportTrackingManager::class);

        $tracking = $trackingManager->getTracking($importIdStr);
        if ($tracking) {
            $trackingManager->markInterrupted($importIdStr, $exception?->getMessage() ?? 'Import failed unexpectedly.');
            $trackingManager->deleteSourceFile($importIdStr);
            return;
        }

        // Legacy
        if (is_numeric($this->importId)) {
            $importService = app(ProjectImportService::class);
            $importService->markImportFailed((int) $this->importId, $exception?->getMessage() ?? 'Import failed unexpectedly.');
            $importService->deleteImportFile((int) $this->importId);
        }
    }
}

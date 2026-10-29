<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectImport;
use App\Models\User;
use App\Services\Contracts\ProjectImportHandlerInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProjectHostingImportService implements ProjectImportHandlerInterface
{
    protected ProjectImportTrackingManager $trackingManager;

    public function __construct(
        protected ProjectHostingService $hostingService,
        protected ProjectHostingImportValidator $validator,
        ?ProjectImportTrackingManager $trackingManager = null
    ) {
        $this->trackingManager = $trackingManager ?? app(ProjectImportTrackingManager::class);
    }

    /**
     * Process the queued project hosting import workbook.
     * Supports both non-database tracked import identifier string and legacy ProjectImport model.
     *
     * @param  \App\Models\ProjectImport|string  $import
     * @return void
     *
     * @throws \Throwable
     */
    public function process(ProjectImport|string $import): void
    {
        if (is_string($import)) {
            $this->processTrackedImport($import);
            return;
        }

        $this->processLegacyImport($import);
    }

    /**
     * Process a non-database tracked import using temporary shared filesystem storage.
     *
     * @param  string  $importId
     * @return void
     */
    protected function processTrackedImport(string $importId): void
    {
        $meta = $this->trackingManager->getTracking($importId);
        if (!$meta) {
            throw new \RuntimeException("Import tracking record not found or expired for ID: {$importId}");
        }

        $filePath = $meta['file_path'];
        if (!Storage::disk(ProjectImportTrackingManager::DISK)->exists($filePath)) {
            throw new FileNotFoundException(
                sprintf('Project hosting import file not found: %s', $filePath)
            );
        }

        $fullPath = Storage::disk(ProjectImportTrackingManager::DISK)->path($filePath);
        $user = User::find($meta['user_id']);

        if (!$user) {
            throw new \RuntimeException('The initiating user for this import could not be found.');
        }

        $this->trackingManager->updateTracking($importId, [
            'status' => ProjectImportTrackingManager::STATUS_PROCESSING,
            'started_at' => now()->toIso8601String(),
        ]);

        Log::info('Processing Project Hosting tracked import', [
            'import_id' => $importId,
            'file_path' => $filePath,
            'user_id' => $user->id,
        ]);

        // Validate workbook structure and rows
        $validation = $this->validator->validate($fullPath, $user);

        if (!$validation['is_valid_structure']) {
            $this->trackingManager->updateTracking($importId, [
                'status' => ProjectImportTrackingManager::STATUS_FAILED,
                'error_message' => $validation['structure_error'] ?? 'Invalid workbook structure.',
                'completed_at' => now()->toIso8601String(),
            ]);
            return;
        }

        if ($validation['total_rows'] === 0) {
            $this->trackingManager->updateTracking($importId, [
                'status' => ProjectImportTrackingManager::STATUS_FAILED,
                'error_message' => 'No non-blank data rows found in the workbook.',
                'completed_at' => now()->toIso8601String(),
            ]);
            return;
        }

        $totalRows = count($validation['rows']);
        $this->trackingManager->updateTracking($importId, [
            'total_rows' => $totalRows,
        ]);

        $successfulCount = 0;
        $failedCount = 0;
        $rowResults = [];

        foreach ($validation['rows'] as $rowItem) {
            $excelRow = isset($rowItem['row_number']) ? (int) $rowItem['row_number'] : null;
            $projectCode = $rowItem['project_code'] ?? '-';
            $serviceName = $rowItem['service_name'] ?? '-';
            $rawValues = $rowItem['raw_values'] ?? [];

            // If row has validation errors: mark it Failed and continue
            if (($rowItem['status'] ?? null) !== 'Valid') {
                $failedCount++;
                $rowResults[] = [
                    'excel_row' => $excelRow,
                    'project_code' => $projectCode,
                    'service_name' => $serviceName,
                    'status' => 'failed',
                    'message' => implode('; ', $rowItem['errors'] ?? ['Validation failed']),
                    'raw_values' => $rawValues,
                ];

                $this->trackingManager->updateTracking($importId, [
                    'failed_rows' => $failedCount,
                    'processed_rows' => count($rowResults),
                    'row_results' => $rowResults,
                ]);
                continue;
            }

            // Valid row: process in an isolated database transaction per row
            try {
                DB::transaction(function () use ($rowItem, $user) {
                    $project = Project::findOrFail($rowItem['data']['project_id']);

                    if (!$user->is_super_admin && !$user->can('update', $project)) {
                        throw new \RuntimeException("Unauthorized to add hosting records to project {$project->project_code}.");
                    }

                    $this->hostingService->createRecord(
                        $project,
                        $rowItem['data'],
                        $user->id,
                        preserveLastRenewedDate: true
                    );
                });

                $successfulCount++;
                $rowResults[] = [
                    'excel_row' => $excelRow,
                    'project_code' => $projectCode,
                    'service_name' => $serviceName,
                    'status' => 'success',
                    'message' => 'Record created.',
                    'raw_values' => $rawValues,
                ];

                $this->trackingManager->updateTracking($importId, [
                    'successful_rows' => $successfulCount,
                    'processed_rows' => count($rowResults),
                    'row_results' => $rowResults,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($this->isConnectionException($e)) {
                    $this->handleSystemFailure($importId, $rowResults, $totalRows, $failedCount, $successfulCount, 'Database connection was lost during processing.');
                    throw $e;
                }

                $failedCount++;
                $rowResults[] = [
                    'excel_row' => $excelRow,
                    'project_code' => $projectCode,
                    'service_name' => $serviceName,
                    'status' => 'failed',
                    'message' => $this->sanitizeErrorMessage($e->getMessage()),
                    'raw_values' => $rawValues,
                ];

                $this->trackingManager->updateTracking($importId, [
                    'failed_rows' => $failedCount,
                    'processed_rows' => count($rowResults),
                    'row_results' => $rowResults,
                ]);
            } catch (Throwable $e) {
                $failedCount++;
                $rowResults[] = [
                    'excel_row' => $excelRow,
                    'project_code' => $projectCode,
                    'service_name' => $serviceName,
                    'status' => 'failed',
                    'message' => $this->sanitizeErrorMessage($e->getMessage()),
                    'raw_values' => $rawValues,
                ];

                $this->trackingManager->updateTracking($importId, [
                    'failed_rows' => $failedCount,
                    'processed_rows' => count($rowResults),
                    'row_results' => $rowResults,
                ]);
            }
        }

        $finalStatus = $failedCount === 0
            ? ProjectImportTrackingManager::STATUS_COMPLETED
            : ($successfulCount > 0
                ? ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS
                : ProjectImportTrackingManager::STATUS_FAILED);

        $this->trackingManager->updateTracking($importId, [
            'status' => $finalStatus,
            'total_rows' => $totalRows,
            'processed_rows' => count($rowResults),
            'successful_rows' => $successfulCount,
            'failed_rows' => $failedCount,
            'row_results' => $rowResults,
            'completed_at' => now()->toIso8601String(),
        ]);

        Log::info('Finished Project Hosting tracked import', [
            'import_id' => $importId,
            'status' => $finalStatus,
            'successful' => $successfulCount,
            'failed' => $failedCount,
        ]);
    }

    /**
     * Handle unexpected system-level failure and record unprocessed rows explicitly.
     */
    protected function handleSystemFailure(
        string $importId,
        array $rowResults,
        int $totalRows,
        int $failedCount,
        int $successfulCount,
        string $errorMessage
    ): void {
        $processedCount = count($rowResults);
        for ($i = $processedCount + 1; $i <= $totalRows; $i++) {
            $rowResults[] = [
                'excel_row' => $i + 1,
                'project_code' => '-',
                'service_name' => '-',
                'status' => 'unprocessed',
                'message' => 'Processing was interrupted before this row could be processed.',
                'raw_values' => [],
            ];
        }

        $this->trackingManager->updateTracking($importId, [
            'status' => ProjectImportTrackingManager::STATUS_INTERRUPTED,
            'error_message' => $errorMessage,
            'processed_rows' => $processedCount,
            'successful_rows' => $successfulCount,
            'failed_rows' => $failedCount,
            'row_results' => $rowResults,
            'completed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Sanitize error message to prevent leaking SQL, paths, or credentials.
     */
    protected function sanitizeErrorMessage(string $message): string
    {
        if (str_contains($message, 'SQLSTATE') || str_contains($message, 'QueryException')) {
            return 'A database constraint error occurred while saving this record.';
        }

        // Strip server filesystem paths
        $sanitized = preg_replace('/(\/[a-zA-Z0-9_\-\.]+)+/', '[internal path]', $message);

        return trim($sanitized ?? $message);
    }

    /**
     * Check if an exception is a fatal database connection failure.
     */
    protected function isConnectionException(Throwable $e): bool
    {
        $msg = $e->getMessage();
        return str_contains($msg, 'server has gone away')
            || str_contains($msg, 'Connection refused')
            || str_contains($msg, 'No connection could be made')
            || str_contains($msg, 'Connection timed out')
            || ($e instanceof \Illuminate\Database\QueryException && str_contains($msg, '2006'));
    }

    /**
     * Legacy import execution for existing ProjectImport database model.
     *
     * @param  \App\Models\ProjectImport  $import
     * @return void
     */
    protected function processLegacyImport(ProjectImport $import): void
    {
        if (!Storage::disk('local')->exists($import->file_path)) {
            throw new FileNotFoundException(
                sprintf('Project hosting import file not found: %s', $import->file_path)
            );
        }

        $fullPath = Storage::disk('local')->path($import->file_path);
        $user = $import->uploader ?? User::find($import->uploaded_by);

        if (!$user) {
            throw new \RuntimeException('The initiating user for this import could not be found.');
        }

        $validation = $this->validator->validate($fullPath, $user);

        if (!$validation['is_valid_structure']) {
            $import->update([
                'status' => ProjectImport::STATUS_FAILED,
                'error_message' => $validation['structure_error'] ?? 'Invalid workbook structure.',
                'failed_at' => now(),
            ]);
            return;
        }

        if ($validation['total_rows'] === 0) {
            $import->update([
                'status' => ProjectImport::STATUS_FAILED,
                'error_message' => 'No non-blank data rows found in the workbook.',
                'failed_at' => now(),
            ]);
            return;
        }

        $completedRows = [];
        if (!empty($import->row_results) && is_array($import->row_results)) {
            foreach ($import->row_results as $res) {
                if (isset($res['excel_row'])) {
                    $completedRows[(int) $res['excel_row']] = $res;
                }
            }
        }

        $successfulCount = $import->successful_rows ?? 0;
        $failedCount = $import->failed_rows ?? 0;
        $rowResults = $completedRows;

        foreach ($validation['rows'] as $rowItem) {
            $excelRow = isset($rowItem['row_number']) ? (int) $rowItem['row_number'] : null;
            $projectCode = $rowItem['project_code'] ?? null;
            $serviceName = $rowItem['service_name'] ?? null;

            if ($excelRow !== null && isset($completedRows[$excelRow]) && ($completedRows[$excelRow]['status'] ?? '') === 'success') {
                continue;
            }

            if (($rowItem['status'] ?? null) !== 'Valid') {
                $failedCount++;
                if ($excelRow !== null) {
                    $rowResults[$excelRow] = [
                        'excel_row' => $excelRow,
                        'project_code' => $projectCode,
                        'service_name' => $serviceName,
                        'status' => 'failed',
                        'message' => implode('; ', $rowItem['errors'] ?? ['Validation failed']),
                        'record_id' => null,
                    ];
                }
                continue;
            }

            try {
                $recordId = DB::transaction(function () use ($rowItem, $user) {
                    $project = Project::findOrFail($rowItem['data']['project_id']);

                    if (!$user->is_super_admin && !$user->can('update', $project)) {
                        throw new \RuntimeException("Unauthorized to add hosting records to project {$project->project_code}.");
                    }

                    $record = $this->hostingService->createRecord(
                        $project,
                        $rowItem['data'],
                        $user->id,
                        preserveLastRenewedDate: true
                    );

                    return $record->id;
                });

                $successfulCount++;
                if ($excelRow !== null) {
                    $rowResults[$excelRow] = [
                        'excel_row' => $excelRow,
                        'project_code' => $projectCode,
                        'service_name' => $serviceName,
                        'status' => 'success',
                        'message' => 'Record created.',
                        'record_id' => $recordId,
                    ];
                }
            } catch (Throwable $e) {
                $failedCount++;
                if ($excelRow !== null) {
                    $rowResults[$excelRow] = [
                        'excel_row' => $excelRow,
                        'project_code' => $projectCode,
                        'service_name' => $serviceName,
                        'status' => 'failed',
                        'message' => $this->sanitizeErrorMessage($e->getMessage()),
                        'record_id' => null,
                    ];
                }
            }
        }

        $totalRows = count($validation['rows']);
        $finalStatus = $failedCount === 0
            ? ProjectImport::STATUS_COMPLETED
            : ($successfulCount > 0 ? ProjectImport::STATUS_COMPLETED_WITH_ERRORS : ProjectImport::STATUS_FAILED);

        $import->update([
            'status' => $finalStatus,
            'total_rows' => $totalRows,
            'processed_rows' => $totalRows,
            'successful_rows' => $successfulCount,
            'failed_rows' => $failedCount,
            'row_results' => array_values($rowResults),
            'completed_at' => now(),
        ]);
    }
}

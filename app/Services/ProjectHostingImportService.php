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
    public function __construct(
        protected ProjectHostingService $hostingService,
        protected ProjectHostingImportValidator $validator
    ) {}

    /**
     * Process the queued project hosting import workbook.
     *
     * @param  \App\Models\ProjectImport  $import
     * @return void
     *
     * @throws \Throwable
     */
    public function process(ProjectImport $import): void
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

        Log::info('Processing Project Hosting import', [
            'import_id' => $import->id,
            'file_path' => $import->file_path,
            'user_id' => $user->id,
        ]);

        // Validate workbook structure and rows
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

        $import->refresh();

        // Load existing row results indexed by excel_row to ensure idempotency on job retries
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

            // Idempotency: If this row was already successfully imported in a prior attempt/retry, skip it
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

                $import->update([
                    'failed_rows' => $failedCount,
                    'processed_rows' => count($rowResults),
                    'row_results' => array_values($rowResults),
                ]);
                continue;
            }

            try {
                $recordId = DB::transaction(function () use ($import, $rowItem, $user, &$rowResults, &$successfulCount, $excelRow, $projectCode, $serviceName) {
                    $project = Project::findOrFail($rowItem['data']['project_id']);

                    // Verify user still has access and update permission on this project
                    if (!$user->is_super_admin && !$user->can('update', $project)) {
                        throw new \RuntimeException("Unauthorized to add hosting records to project {$project->project_code}.");
                    }

                    $record = $this->hostingService->createRecord(
                        $project,
                        $rowItem['data'],
                        $user->id,
                        preserveLastRenewedDate: true
                    );

                    $successfulCount++;
                    if ($excelRow !== null) {
                        $rowResults[$excelRow] = [
                            'excel_row' => $excelRow,
                            'project_code' => $projectCode,
                            'service_name' => $serviceName,
                            'status' => 'success',
                            'message' => 'Hosting record created successfully.',
                            'record_id' => $record->id,
                        ];
                    }

                    // Save row progress atomically inside the database transaction
                    $import->update([
                        'successful_rows' => $successfulCount,
                        'processed_rows' => count($rowResults),
                        'row_results' => array_values($rowResults),
                    ]);

                    return $record->id;
                });
            } catch (Throwable $e) {
                $failedCount++;
                Log::warning('Failed importing hosting record row', [
                    'import_id' => $import->id,
                    'excel_row' => $excelRow,
                    'error' => $e->getMessage(),
                ]);

                if ($excelRow !== null) {
                    $rowResults[$excelRow] = [
                        'excel_row' => $excelRow,
                        'project_code' => $projectCode,
                        'service_name' => $serviceName,
                        'status' => 'failed',
                        'message' => $e->getMessage(),
                        'record_id' => null,
                    ];
                }

                $import->update([
                    'failed_rows' => $failedCount,
                    'processed_rows' => count($rowResults),
                    'row_results' => array_values($rowResults),
                ]);
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

        Log::info('Finished Project Hosting import', [
            'import_id' => $import->id,
            'status' => $finalStatus,
            'successful' => $successfulCount,
            'failed' => $failedCount,
        ]);
    }
}

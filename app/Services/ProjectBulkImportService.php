<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectImport;
use App\Models\ProjectTimeline;
use App\Models\User;
use App\Services\Contracts\ProjectImportHandlerInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\CauserResolver;
use Throwable;

class ProjectBulkImportService implements ProjectImportHandlerInterface
{
    public const STATUS_CREATED = 'created';

    public const STATUS_UPDATED = 'updated';

    public const STATUS_UNCHANGED = 'unchanged';

    public const STATUS_FAILED = 'failed';

    protected ProjectImportTrackingManager $trackingManager;

    public function __construct(
        protected ProjectServices $projectServices,
        protected ProjectBulkImportValidator $validator,
        ?ProjectImportTrackingManager $trackingManager = null
    ) {
        $this->trackingManager = $trackingManager ?? app(ProjectImportTrackingManager::class);
    }

    /**
     * Process the queued bulk projects import workbook.
     */
    public function process(ProjectImport|string $import): void
    {
        if (! is_string($import)) {
            throw new \InvalidArgumentException('Bulk projects import only supports UUID-tracked imports.');
        }

        $this->processTrackedImport($import);
    }

    protected function processTrackedImport(string $importId): void
    {
        $meta = $this->trackingManager->getTracking($importId);
        if (! $meta) {
            throw new \RuntimeException("Import tracking record not found or expired for ID: {$importId}");
        }

        $filePath = $meta['file_path'];
        if (! Storage::disk(ProjectImportTrackingManager::DISK)->exists($filePath)) {
            throw new FileNotFoundException(
                sprintf('Bulk projects import file not found: %s', $filePath)
            );
        }

        $fullPath = Storage::disk(ProjectImportTrackingManager::DISK)->path($filePath);
        $user = User::find($meta['user_id']);

        if (! $user) {
            throw new \RuntimeException('The initiating user for this import could not be found.');
        }

        $this->trackingManager->updateTracking($importId, [
            'status' => ProjectImportTrackingManager::STATUS_PROCESSING,
            'started_at' => now()->toIso8601String(),
        ]);

        Log::info('Processing Bulk Projects tracked import', [
            'import_id' => $importId,
            'file_path' => $filePath,
            'user_id' => $user->id,
        ]);

        $validation = $this->validator->validate($fullPath, $user);

        if (! $validation['is_valid_structure']) {
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

        // Idempotency guard: preserve already-finished rows if re-invoked after partial run
        $existingResultsByRow = [];
        foreach ((array) ($meta['row_results'] ?? []) as $existingOutcome) {
            $rowNum = isset($existingOutcome['excel_row']) ? (int) $existingOutcome['excel_row'] : null;
            $status = strtolower((string) ($existingOutcome['status'] ?? ''));
            if ($rowNum !== null && in_array($status, [self::STATUS_CREATED, self::STATUS_UPDATED, self::STATUS_UNCHANGED, self::STATUS_FAILED], true)) {
                $existingResultsByRow[$rowNum] = $existingOutcome;
            }
        }

        $createdCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
        $failedCount = 0;
        $rowResults = [];

        $previousCauser = CauserResolver::resolve();
        CauserResolver::setCauser($user);

        try {
            foreach ($validation['rows'] as $rowItem) {
                $excelRow = isset($rowItem['row_number']) ? (int) $rowItem['row_number'] : null;

                // Skip rows already processed earlier to prevent duplicate creation on replay
                if ($excelRow !== null && isset($existingResultsByRow[$excelRow])) {
                    $cached = $existingResultsByRow[$excelRow];
                    $rowResults[] = $cached;
                    $cachedStatus = strtolower((string) ($cached['status'] ?? ''));
                    if ($cachedStatus === self::STATUS_CREATED) {
                        $createdCount++;
                    } elseif ($cachedStatus === self::STATUS_UPDATED) {
                        $updatedCount++;
                    } elseif ($cachedStatus === self::STATUS_UNCHANGED) {
                        $unchangedCount++;
                    } else {
                        $failedCount++;
                    }
                    continue;
                }

                $projectCode = $rowItem['project_code'] ?? '-';
                $projectName = $rowItem['project_name'] ?? '-';
                $rawValues = $rowItem['raw_values'] ?? [];

                if (($rowItem['status'] ?? null) !== 'Valid') {
                    $failedCount++;
                    $rowResults[] = [
                        'excel_row' => $excelRow,
                        'project_code' => $projectCode,
                        'project_name' => $projectName,
                        'status' => self::STATUS_FAILED,
                        'message' => implode('; ', $rowItem['errors'] ?? ['Validation failed']),
                        'raw_values' => $rawValues,
                    ];

                    $this->persistProgress($importId, $createdCount, $updatedCount, $unchangedCount, $failedCount, $rowResults);
                    continue;
                }

                try {
                    if (($rowItem['operation'] ?? 'create') === 'create') {
                        if (! $user->is_super_admin && ! $user->can('project.create')) {
                            throw new \RuntimeException('You do not have permission to create projects.');
                        }

                        $createdProject = $this->projectServices->create($rowItem['data'], $user);

                        $createdCount++;
                        $rowResults[] = [
                            'excel_row' => $excelRow,
                            'project_code' => $createdProject->project_code,
                            'project_name' => $createdProject->name,
                            'status' => self::STATUS_CREATED,
                            'message' => "Project created ({$createdProject->project_code}).",
                            'raw_values' => $rawValues,
                        ];
                    } else {
                        if (! $user->is_super_admin && ! $user->can('project.edit')) {
                            throw new \RuntimeException('You do not have permission to edit projects.');
                        }

                        $outcome = DB::transaction(function () use ($rowItem, $user): array {
                            /** @var Project $project */
                            $project = Project::query()
                                ->with(['projectTimelines', 'serviceTypes:id', 'technologies:id'])
                                ->where('id', $rowItem['data']['project_id'])
                                ->where('is_system', false)
                                ->whereNull('deleted_at')
                                ->lockForUpdate()
                                ->firstOrFail();

                            if (! $user->is_super_admin && ! $user->can('update', $project)) {
                                throw new \RuntimeException("You are not authorized to update project '{$project->project_code}'.");
                            }

                            $this->assertOriginalTimelineCanBeUpdated($project, $rowItem['data']);

                            if (! $this->hasActualChanges($project, $rowItem['data'])) {
                                return [
                                    'status' => self::STATUS_UNCHANGED,
                                    'project_code' => $project->project_code,
                                    'project_name' => $project->name,
                                    'message' => 'No changes detected.',
                                ];
                            }

                            $updatedProject = $this->projectServices->updateFromImport($project, $rowItem['data'], $user);

                            return [
                                'status' => self::STATUS_UPDATED,
                                'project_code' => $updatedProject->project_code,
                                'project_name' => $updatedProject->name,
                                'message' => 'Project updated.',
                            ];
                        });

                        if ($outcome['status'] === self::STATUS_UNCHANGED) {
                            $unchangedCount++;
                        } else {
                            $updatedCount++;
                        }

                        $rowResults[] = [
                            'excel_row' => $excelRow,
                            'project_code' => $outcome['project_code'],
                            'project_name' => $outcome['project_name'],
                            'status' => $outcome['status'],
                            'message' => $outcome['message'],
                            'raw_values' => $rawValues,
                        ];
                    }

                    $this->persistProgress($importId, $createdCount, $updatedCount, $unchangedCount, $failedCount, $rowResults);
                } catch (QueryException $e) {
                    if ($this->isConnectionException($e)) {
                        $this->handleSystemFailure(
                            $importId,
                            $rowResults,
                            $totalRows,
                            $createdCount,
                            $updatedCount,
                            $unchangedCount,
                            $failedCount,
                            'Database connection was lost during processing.'
                        );
                        throw $e;
                    }

                    $failedCount++;
                    $rowResults[] = [
                        'excel_row' => $excelRow,
                        'project_code' => $projectCode,
                        'project_name' => $projectName,
                        'status' => self::STATUS_FAILED,
                        'message' => $this->sanitizeErrorMessage($e->getMessage()),
                        'raw_values' => $rawValues,
                    ];

                    $this->persistProgress($importId, $createdCount, $updatedCount, $unchangedCount, $failedCount, $rowResults);
                } catch (Throwable $e) {
                    $failedCount++;
                    $rowResults[] = [
                        'excel_row' => $excelRow,
                        'project_code' => $projectCode,
                        'project_name' => $projectName,
                        'status' => self::STATUS_FAILED,
                        'message' => $this->sanitizeErrorMessage($e->getMessage()),
                        'raw_values' => $rawValues,
                    ];

                    $this->persistProgress($importId, $createdCount, $updatedCount, $unchangedCount, $failedCount, $rowResults);
                }
            }
        } finally {
            CauserResolver::setCauser($previousCauser);
        }

        $successTotal = $createdCount + $updatedCount + $unchangedCount;
        $finalStatus = $failedCount === 0
            ? ProjectImportTrackingManager::STATUS_COMPLETED
            : ($successTotal > 0
                ? ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS
                : ProjectImportTrackingManager::STATUS_FAILED);

        $this->trackingManager->updateTracking($importId, [
            'status' => $finalStatus,
            'total_rows' => $totalRows,
            'processed_rows' => count($rowResults),
            'successful_rows' => $successTotal,
            'created_rows' => $createdCount,
            'updated_rows' => $updatedCount,
            'unchanged_rows' => $unchangedCount,
            'failed_rows' => $failedCount,
            'row_results' => $rowResults,
            'completed_at' => now()->toIso8601String(),
        ]);

        Log::info('Finished Bulk Projects tracked import', [
            'import_id' => $importId,
            'status' => $finalStatus,
            'created' => $createdCount,
            'updated' => $updatedCount,
            'unchanged' => $unchangedCount,
            'failed' => $failedCount,
        ]);
    }

    /**
     * Persist row-by-row tracking metrics to meta.json.
     */
    protected function persistProgress(
        string $importId,
        int $createdCount,
        int $updatedCount,
        int $unchangedCount,
        int $failedCount,
        array $rowResults
    ): void {
        $this->trackingManager->updateTracking($importId, [
            'processed_rows' => count($rowResults),
            'successful_rows' => $createdCount + $updatedCount + $unchangedCount,
            'created_rows' => $createdCount,
            'updated_rows' => $updatedCount,
            'unchanged_rows' => $unchangedCount,
            'failed_rows' => $failedCount,
            'row_results' => $rowResults,
        ]);
    }

    /**
     * Re-verify Original Timeline safety under row lock before mutating an existing project.
     *
     * @param  array<string, mixed>  $data
     */
    protected function assertOriginalTimelineCanBeUpdated(Project $project, array $data): void
    {
        $hasTimelineField = array_key_exists('timeline_name', $data)
            || array_key_exists('start_date', $data)
            || array_key_exists('end_date', $data)
            || array_key_exists('customer_end_date', $data)
            || array_key_exists('estimated_time_seconds', $data)
            || array_key_exists('customer_estimate_seconds', $data);

        if (! $hasTimelineField) {
            return;
        }

        $originalTimelines = $project->projectTimelines->where('type', 'original');
        if ($originalTimelines->count() !== 1) {
            throw new \RuntimeException('Original Timeline could not be uniquely identified for this project.');
        }

        /** @var ProjectTimeline $originalTimeline */
        $originalTimeline = $originalTimelines->first();

        $effectiveStart = $data['start_date'] ?? $originalTimeline->start_date?->toDateString() ?? $project->start_date?->toDateString();
        $effectiveEnd = $data['end_date'] ?? $originalTimeline->end_date?->toDateString() ?? $project->end_date?->toDateString();
        $effectiveCustomerEnd = $data['customer_end_date'] ?? $originalTimeline->customer_end_date?->toDateString() ?? $project->customer_end_date?->toDateString();

        if ($effectiveStart !== null && $effectiveEnd !== null && $effectiveEnd < $effectiveStart) {
            throw new \RuntimeException('End Date must be on or after Start Date.');
        }

        if ($effectiveEnd !== null && $effectiveCustomerEnd !== null && $effectiveCustomerEnd < $effectiveEnd) {
            throw new \RuntimeException('Customer End Date must be on or after End Date.');
        }

        if (in_array((int) $originalTimeline->status, [ProjectTimeline::STATUS_COMPLETED, ProjectTimeline::STATUS_CANCELLED], true)) {
            $attemptsChange = (array_key_exists('timeline_name', $data) && $data['timeline_name'] !== $originalTimeline->name)
                || (array_key_exists('start_date', $data) && $data['start_date'] !== $originalTimeline->start_date?->toDateString())
                || (array_key_exists('end_date', $data) && $data['end_date'] !== $originalTimeline->end_date?->toDateString())
                || (array_key_exists('customer_end_date', $data) && $data['customer_end_date'] !== $originalTimeline->customer_end_date?->toDateString())
                || (array_key_exists('estimated_time_seconds', $data) && $data['estimated_time_seconds'] !== $originalTimeline->estimated_time_seconds)
                || (array_key_exists('customer_estimate_seconds', $data) && $data['customer_estimate_seconds'] !== $originalTimeline->customer_estimate_seconds);

            if ($attemptsChange) {
                throw new \RuntimeException('Completed or cancelled Original Timeline cannot be modified.');
            }
        }

        if ((array_key_exists('start_date', $data) || array_key_exists('end_date', $data)) && $effectiveStart !== null && $effectiveEnd !== null) {
            $overlapping = $project->projectTimelines
                ->where('id', '!=', $originalTimeline->id)
                ->where('status', '!=', ProjectTimeline::STATUS_CANCELLED)
                ->first(function (ProjectTimeline $timeline) use ($effectiveStart, $effectiveEnd) {
                    $otherStart = $timeline->start_date?->toDateString();
                    $otherEnd = $timeline->end_date?->toDateString();

                    return $otherStart !== null
                        && $otherEnd !== null
                        && $otherStart <= $effectiveEnd
                        && $otherEnd >= $effectiveStart;
                });

            if ($overlapping) {
                throw new \RuntimeException('The date range overlaps with another existing project timeline.');
            }
        }
    }

    /**
     * Determine whether any supplied import fields actually differ from the current project state.
     *
     * @param  array<string, mixed>  $data
     */
    protected function hasActualChanges(Project $project, array $data): bool
    {
        if (array_key_exists('name', $data) && $data['name'] !== $project->name) {
            return true;
        }

        if (array_key_exists('customer_id', $data) && (int) $data['customer_id'] !== (int) ($project->customer_id ?? 0)) {
            return true;
        }

        if (array_key_exists('priority', $data) && $data['priority'] !== $project->priority) {
            return true;
        }

        if (array_key_exists('status_id', $data) && (int) $data['status_id'] !== (int) $project->status_id) {
            return true;
        }

        if (array_key_exists('project_stage_id', $data) && (int) $data['project_stage_id'] !== (int) ($project->project_stage_id ?? 0)) {
            return true;
        }

        if (array_key_exists('domain', $data) && $data['domain'] !== $project->domain) {
            return true;
        }

        if (array_key_exists('sales_person_id', $data) && (int) $data['sales_person_id'] !== (int) ($project->sales_person_id ?? 0)) {
            return true;
        }

        if (array_key_exists('default_billable', $data) && (bool) $data['default_billable'] !== (bool) $project->default_billable) {
            return true;
        }

        if (array_key_exists('project_category_ids', $data)) {
            $existingCategories = collect($project->project_category_ids ?? [])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            $newCategories = collect($data['project_category_ids'])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            if ($existingCategories !== $newCategories) {
                return true;
            }
        }

        if (array_key_exists('service_type_ids', $data)) {
            $existingServiceTypes = $project->serviceTypes->pluck('id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            $newServiceTypes = collect($data['service_type_ids'])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            if ($existingServiceTypes !== $newServiceTypes) {
                return true;
            }
        }

        if (array_key_exists('project_technology_ids', $data)) {
            $existingTechs = $project->technologies->pluck('id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            $newTechs = collect($data['project_technology_ids'])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            if ($existingTechs !== $newTechs) {
                return true;
            }
        }

        $originalTimeline = $project->projectTimelines->where('type', 'original')->first();

        if (array_key_exists('timeline_name', $data) && $originalTimeline && $data['timeline_name'] !== $originalTimeline->name) {
            return true;
        }

        if (array_key_exists('start_date', $data) && (
            $data['start_date'] !== $project->start_date?->toDateString()
            || ($originalTimeline && $data['start_date'] !== $originalTimeline->start_date?->toDateString())
        )) {
            return true;
        }

        if (array_key_exists('end_date', $data) && (
            $data['end_date'] !== $project->end_date?->toDateString()
            || ($originalTimeline && $data['end_date'] !== $originalTimeline->end_date?->toDateString())
        )) {
            return true;
        }

        if (array_key_exists('customer_end_date', $data) && (
            $data['customer_end_date'] !== $project->customer_end_date?->toDateString()
            || ($originalTimeline && $data['customer_end_date'] !== $originalTimeline->customer_end_date?->toDateString())
        )) {
            return true;
        }

        if (array_key_exists('estimated_time_seconds', $data) && (
            $data['estimated_time_seconds'] !== $project->estimated_time_seconds
            || ($originalTimeline && $data['estimated_time_seconds'] !== $originalTimeline->estimated_time_seconds)
        )) {
            return true;
        }

        if (array_key_exists('customer_estimate_seconds', $data) && (
            $data['customer_estimate_seconds'] !== $project->customer_estimate_seconds
            || ($originalTimeline && $data['customer_estimate_seconds'] !== $originalTimeline->customer_estimate_seconds)
        )) {
            return true;
        }

        return false;
    }

    /**
     * Handle unexpected system-level failure and record remaining unprocessed rows explicitly.
     */
    protected function handleSystemFailure(
        string $importId,
        array $rowResults,
        int $totalRows,
        int $createdCount,
        int $updatedCount,
        int $unchangedCount,
        int $failedCount,
        string $errorMessage
    ): void {
        $processedCount = count($rowResults);
        for ($i = $processedCount + 1; $i <= $totalRows; $i++) {
            $rowResults[] = [
                'excel_row' => $i + 1,
                'project_code' => '-',
                'project_name' => '-',
                'status' => 'unprocessed',
                'message' => 'Processing was interrupted before this row could be processed.',
                'raw_values' => [],
            ];
        }

        $this->trackingManager->updateTracking($importId, [
            'status' => ProjectImportTrackingManager::STATUS_INTERRUPTED,
            'error_message' => $errorMessage,
            'processed_rows' => $processedCount,
            'successful_rows' => $createdCount + $updatedCount + $unchangedCount,
            'created_rows' => $createdCount,
            'updated_rows' => $updatedCount,
            'unchanged_rows' => $unchangedCount,
            'failed_rows' => $failedCount,
            'row_results' => $rowResults,
            'completed_at' => now()->toIso8601String(),
        ]);
    }

    protected function sanitizeErrorMessage(string $message): string
    {
        if (str_contains($message, 'SQLSTATE') || str_contains($message, 'QueryException')) {
            return 'A database constraint error occurred while saving this record.';
        }

        $sanitized = preg_replace('/(\/[a-zA-Z0-9_\-\.]+)+/', '[internal path]', $message);

        return trim($sanitized ?? $message);
    }

    protected function isConnectionException(Throwable $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, 'server has gone away')
            || str_contains($msg, 'Connection refused')
            || str_contains($msg, 'No connection could be made')
            || str_contains($msg, 'Connection timed out')
            || ($e instanceof QueryException && str_contains($msg, '2006'));
    }
}


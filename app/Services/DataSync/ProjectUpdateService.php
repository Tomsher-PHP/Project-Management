<?php

namespace App\Services\DataSync;

use App\Models\ApiClient;
use App\Models\DataSyncEntityMapping;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStage;
use App\Models\ProjectStageHistory;
use App\Models\ProjectStatus;
use App\Models\ProjectStatusHistory;
use App\Models\ServiceType;
use App\Models\Technology;
use App\Models\User;
use App\Services\ProjectServices;
use App\Services\ProjectTimelineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ProjectUpdateService
{
    public function __construct(
        protected ProjectServices $projectServices,
        protected ExternalEntityMappingService $mappingService
    ) {}

    /**
     * Update an existing project based on normalized data.
     *
     * @param  string  $projectCode
     * @param  array<string, mixed>  $data
     * @param  ApiClient|null  $apiClient
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException
     * @throws ConflictHttpException
     * @throws ValidationException
     * @throws Throwable
     */
    public function update(string $projectCode, array $data, ?ApiClient $apiClient = null): array
    {
        return DB::transaction(function () use ($projectCode, $data, $apiClient) {
            /** @var Project|null $project */
            $project = Project::query()->where('project_code', $projectCode)->lockForUpdate()->first();

            if (! $project) {
                throw new NotFoundHttpException('Project not found.');
            }

            // 1. External Mapping Verification & Enrichment
            $externalCode = array_key_exists('external_code', $data)
                ? $this->mappingService->sanitizeIdentifier($data['external_code'])
                : null;
            $externalId = array_key_exists('external_id', $data)
                ? $this->mappingService->sanitizeIdentifier($data['external_id'])
                : null;

            if ($apiClient instanceof ApiClient && ($externalCode !== null || $externalId !== null)) {
                $existingMapping = $this->mappingService->findMapping(
                    $apiClient->id,
                    DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
                    $externalCode,
                    $externalId
                );

                if ($existingMapping !== null && (int) $existingMapping->local_id !== (int) $project->id) {
                    throw new ConflictHttpException('External mapping belongs to a different project.');
                }

                $projectMapping = DataSyncEntityMapping::query()
                    ->where('api_client_id', $apiClient->id)
                    ->where('entity_type', DataSyncEntityMapping::ENTITY_TYPE_PROJECT)
                    ->where('local_id', $project->id)
                    ->first();

                if ($projectMapping !== null) {
                    if ($externalCode !== null && $projectMapping->external_code !== null && $projectMapping->external_code !== $externalCode) {
                        throw new ConflictHttpException('Conflicting external project code.');
                    }

                    if ($externalId !== null && $projectMapping->external_id !== null && $projectMapping->external_id !== $externalId) {
                        throw new ConflictHttpException('Conflicting external project id.');
                    }

                    // Safe mapping enrichment if missing a secondary identifier
                    $this->mappingService->enrichMapping($projectMapping, $externalCode, $externalId);
                }
            }

            // 2. Dates Validation (Effective Dates)
            $effectiveStartDate = array_key_exists('start_date', $data)
                ? ($data['start_date'] !== null ? (string) $data['start_date'] : null)
                : $project->start_date?->format('Y-m-d');

            $effectiveEndDate = array_key_exists('end_date', $data)
                ? ($data['end_date'] !== null ? (string) $data['end_date'] : null)
                : $project->end_date?->format('Y-m-d');

            $effectiveCustomerEndDate = array_key_exists('customer_end_date', $data)
                ? ($data['customer_end_date'] !== null ? (string) $data['customer_end_date'] : null)
                : $project->customer_end_date?->format('Y-m-d');

            $dateErrors = [];
            if ($effectiveStartDate && $effectiveEndDate && $effectiveEndDate < $effectiveStartDate) {
                $dateErrors['end_date'] = ['The end date must be greater than or equal to start date.'];
            }

            if ($effectiveEndDate && $effectiveCustomerEndDate && $effectiveCustomerEndDate < $effectiveEndDate) {
                $dateErrors['customer_end_date'] = ['The customer end date must be greater than or equal to end date.'];
            }

            if (! empty($dateErrors)) {
                throw ValidationException::withMessages($dateErrors);
            }

            // 3. Salesperson Resolution
            $resolvedSalesPersonId = null;
            $salesPersonSpecified = false;

            if (array_key_exists('sales_person_email', $data)) {
                $salesPersonSpecified = true;
                $email = $data['sales_person_email'];
                if ($email !== null && trim((string) $email) !== '') {
                    $salesUser = User::query()->where('email', trim((string) $email))->first();
                    if (! $salesUser) {
                        throw ValidationException::withMessages([
                            'sales_person_email' => ['The selected sales person email does not exist.'],
                        ]);
                    }
                    $resolvedSalesPersonId = (int) $salesUser->id;
                } else {
                    $resolvedSalesPersonId = null;
                }
            }

            // 4. Parent Project Resolution
            $resolvedParentProjectId = null;
            $parentProjectSpecified = false;

            if (array_key_exists('parent_project_code', $data)) {
                $parentProjectSpecified = true;
                $parentCode = $data['parent_project_code'];
                if ($parentCode !== null && trim((string) $parentCode) !== '') {
                    $parentProject = Project::query()->where('project_code', trim((string) $parentCode))->first();
                    if (! $parentProject) {
                        throw ValidationException::withMessages([
                            'parent_project_code' => ['The selected parent project code does not exist.'],
                        ]);
                    }

                    if ((int) $parentProject->id === (int) $project->id) {
                        throw ValidationException::withMessages([
                            'parent_project_code' => ['A project cannot be its own parent.'],
                        ]);
                    }

                    // Check circular ancestry
                    $ancestor = $parentProject;
                    while ($ancestor && $ancestor->parent_project_id !== null) {
                        if ((int) $ancestor->parent_project_id === (int) $project->id) {
                            throw ValidationException::withMessages([
                                'parent_project_code' => ['Circular parent project relationship detected.'],
                            ]);
                        }
                        $ancestor = Project::find($ancestor->parent_project_id);
                    }

                    $resolvedParentProjectId = (int) $parentProject->id;
                } else {
                    $resolvedParentProjectId = null;
                }
            }

            // 5. Status Resolution (Valid -> Default -> Omitted preserves existing)
            $resolvedStatusId = null;
            $statusSpecified = false;

            if (array_key_exists('status_code', $data)) {
                $statusSpecified = true;
                $statusCode = $data['status_code'];
                $status = null;
                if ($statusCode !== null && trim((string) $statusCode) !== '') {
                    $status = ProjectStatus::query()->where('code', trim((string) $statusCode))->first();
                }
                if (! $status) {
                    $status = ProjectStatus::query()->where('is_default', 1)->first();
                }
                if (! $status) {
                    throw new \RuntimeException('Default project status is not configured.');
                }
                $resolvedStatusId = (int) $status->id;
            }

            // 6. Stage Resolution (Valid -> Default -> Omitted preserves existing)
            $resolvedStageId = null;
            $stageSpecified = false;

            if (array_key_exists('stage_code', $data)) {
                $stageSpecified = true;
                $stageCode = $data['stage_code'];
                $stage = null;
                if ($stageCode !== null && trim((string) $stageCode) !== '') {
                    $stage = ProjectStage::query()->where('code', trim((string) $stageCode))->first();
                }
                if (! $stage) {
                    $stage = ProjectStage::query()->where('is_default', 1)->first();
                }
                if (! $stage) {
                    throw new \RuntimeException('Default project stage is not configured.');
                }
                $resolvedStageId = (int) $stage->id;
            }

            // 7. Relationships Resolution
            $technologiesSpecified = false;
            $resolvedTechnologyIds = [];
            if (array_key_exists('technology_codes', $data)) {
                $technologiesSpecified = true;
                if (is_array($data['technology_codes']) && ! empty($data['technology_codes'])) {
                    $codes = array_values(array_filter(array_map('strval', $data['technology_codes'])));
                    if (! empty($codes)) {
                        $resolvedTechnologyIds = Technology::query()
                            ->whereIn('code', $codes)
                            ->pluck('id')
                            ->map(fn($id) => (int) $id)
                            ->unique()
                            ->values()
                            ->all();
                    }
                }
            }

            $serviceTypesSpecified = false;
            $resolvedServiceTypeIds = [];
            if (array_key_exists('service_type_codes', $data)) {
                $serviceTypesSpecified = true;
                if (is_array($data['service_type_codes']) && ! empty($data['service_type_codes'])) {
                    $codes = array_values(array_filter(array_map('strval', $data['service_type_codes'])));
                    if (! empty($codes)) {
                        $resolvedServiceTypeIds = ServiceType::query()
                            ->whereIn('code', $codes)
                            ->pluck('id')
                            ->map(fn($id) => (int) $id)
                            ->unique()
                            ->values()
                            ->all();
                    }
                }
            }

            $categoriesSpecified = false;
            $resolvedCategoryIds = null;
            if (array_key_exists('category_codes', $data)) {
                $categoriesSpecified = true;
                if (is_array($data['category_codes']) && ! empty($data['category_codes'])) {
                    $codes = array_values(array_filter(array_map('strval', $data['category_codes'])));
                    if (! empty($codes)) {
                        $resolvedCategoryIds = ProjectCategory::query()
                            ->whereIn('code', $codes)
                            ->pluck('id')
                            ->map(fn($id) => (int) $id)
                            ->unique()
                            ->values()
                            ->all();
                    } else {
                        $resolvedCategoryIds = [];
                    }
                } else {
                    $resolvedCategoryIds = [];
                }
            }

            // 8. Estimates Conversion
            $estimatedSeconds = null;
            $estimatedSecondsSpecified = false;
            if (array_key_exists('estimated_time_minutes', $data)) {
                $estimatedSecondsSpecified = true;
                $estimatedSeconds = $data['estimated_time_minutes'] !== null
                    ? (int) $data['estimated_time_minutes'] * 60
                    : null;
            }

            $customerEstimateSeconds = null;
            $customerEstimateSecondsSpecified = false;
            if (array_key_exists('customer_estimate_minutes', $data)) {
                $customerEstimateSecondsSpecified = true;
                $customerEstimateSeconds = $data['customer_estimate_minutes'] !== null
                    ? (int) $data['customer_estimate_minutes'] * 60
                    : null;
            }

            $defaultTaskSeconds = null;
            $defaultTaskSecondsSpecified = false;
            if (array_key_exists('default_task_estimate_seconds', $data)) {
                $defaultTaskSecondsSpecified = true;
                $defaultTaskSeconds = $data['default_task_estimate_seconds'] !== null
                    ? (int) $data['default_task_estimate_seconds']
                    : null;
            }

            // 9. Attribute Updates & Change Detection
            $hasChanges = false;
            $attributesToUpdate = [];

            if (array_key_exists('name', $data) && $project->name !== $data['name']) {
                $attributesToUpdate['name'] = $data['name'];
            }
            if (array_key_exists('project_flow', $data) && $project->project_flow !== $data['project_flow']) {
                $attributesToUpdate['project_flow'] = $data['project_flow'];
            }
            if (array_key_exists('priority', $data) && $project->priority !== $data['priority']) {
                $attributesToUpdate['priority'] = $data['priority'];
            }
            if (array_key_exists('start_date', $data) && $project->start_date?->format('Y-m-d') !== $data['start_date']) {
                $attributesToUpdate['start_date'] = $data['start_date'];
            }
            if (array_key_exists('end_date', $data) && $project->end_date?->format('Y-m-d') !== $data['end_date']) {
                $attributesToUpdate['end_date'] = $data['end_date'];
            }
            if (array_key_exists('customer_end_date', $data) && $project->customer_end_date?->format('Y-m-d') !== $data['customer_end_date']) {
                $attributesToUpdate['customer_end_date'] = $data['customer_end_date'];
            }
            if ($estimatedSecondsSpecified && (int) $project->estimated_time_seconds !== (int) $estimatedSeconds) {
                $attributesToUpdate['estimated_time_seconds'] = $estimatedSeconds;
            }
            if ($customerEstimateSecondsSpecified && (int) $project->customer_estimate_seconds !== (int) $customerEstimateSeconds) {
                $attributesToUpdate['customer_estimate_seconds'] = $customerEstimateSeconds;
            }
            if ($defaultTaskSecondsSpecified && (int) $project->default_task_estimate_seconds !== (int) $defaultTaskSeconds) {
                $attributesToUpdate['default_task_estimate_seconds'] = $defaultTaskSeconds;
            }
            if (array_key_exists('domain', $data) && $project->domain !== $data['domain']) {
                $attributesToUpdate['domain'] = $data['domain'];
            }
            if ($salesPersonSpecified && (int) ($project->sales_person_id ?? 0) !== (int) ($resolvedSalesPersonId ?? 0)) {
                $attributesToUpdate['sales_person_id'] = $resolvedSalesPersonId;
            }
            if ($parentProjectSpecified && (int) ($project->parent_project_id ?? 0) !== (int) ($resolvedParentProjectId ?? 0)) {
                $attributesToUpdate['parent_project_id'] = $resolvedParentProjectId;
            }
            if (array_key_exists('default_billable', $data) && (bool) $project->default_billable !== (bool) $data['default_billable']) {
                $attributesToUpdate['default_billable'] = (bool) $data['default_billable'];
            }
            if (array_key_exists('is_active', $data) && (bool) $project->is_active !== (bool) $data['is_active']) {
                $attributesToUpdate['is_active'] = (bool) $data['is_active'];
            }
            if ($categoriesSpecified) {
                $currentCats = is_array($project->project_category_ids)
                    ? array_values(array_map('intval', $project->project_category_ids))
                    : [];
                sort($currentCats);
                $newCats = array_values(array_map('intval', $resolvedCategoryIds));
                sort($newCats);
                if ($currentCats !== $newCats) {
                    $attributesToUpdate['project_category_ids'] = ! empty($resolvedCategoryIds) ? $resolvedCategoryIds : null;
                }
            }

            if (! empty($attributesToUpdate)) {
                $project->update($attributesToUpdate);
                $hasChanges = true;
            }

            // 10. Status History Update
            if ($statusSpecified && (int) $project->status_id !== $resolvedStatusId) {
                $fromStatusId = $project->status_id;
                $project->update(['status_id' => $resolvedStatusId]);

                ProjectStatusHistory::create([
                    'project_id' => $project->id,
                    'from_status_id' => $fromStatusId,
                    'status_id' => $resolvedStatusId,
                    'added_by' => null,
                ]);
                $hasChanges = true;
            }

            // 11. Stage History Update
            if ($stageSpecified && (int) ($project->project_stage_id ?? 0) !== $resolvedStageId) {
                $fromStageId = $project->project_stage_id;
                $project->update(['project_stage_id' => $resolvedStageId]);

                ProjectStageHistory::create([
                    'project_id' => $project->id,
                    'from_stage_id' => $fromStageId,
                    'stage_id' => $resolvedStageId,
                    'added_by' => null,
                ]);
                $hasChanges = true;
            }

            // 12. Relationships Synchronization
            if ($technologiesSpecified) {
                $currentTechIds = $project->technologies()->pluck('technologies.id')->map(fn($id) => (int) $id)->all();
                sort($currentTechIds);
                $newTechIds = array_values($resolvedTechnologyIds);
                sort($newTechIds);
                if ($currentTechIds !== $newTechIds) {
                    $project->technologies()->sync($resolvedTechnologyIds);
                    $hasChanges = true;
                }
            }

            if ($serviceTypesSpecified) {
                $currentServiceIds = $project->serviceTypes()->pluck('service_types.id')->map(fn($id) => (int) $id)->all();
                sort($currentServiceIds);
                $newServiceIds = array_values($resolvedServiceTypeIds);
                sort($newServiceIds);
                if ($currentServiceIds !== $newServiceIds) {
                    $project->serviceTypes()->sync($resolvedServiceTypeIds);
                    $hasChanges = true;
                }
            }

            // 13. Synchronize Original Timeline if needed
            if ($hasChanges) {
                app(ProjectTimelineService::class)->syncOriginalTimelineFromProject($project);
            }

            return [
                'project_id' => $project->id,
                'project_code' => $project->project_code,
                'updated' => $hasChanges,
            ];
        });
    }
}


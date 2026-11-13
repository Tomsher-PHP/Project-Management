<?php

namespace App\Services\DataSync;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\DataSyncEntityMapping;
use App\Models\Project;
use App\Models\ProjectStageHistory;
use App\Models\ProjectStatusHistory;
use App\Models\ProjectTimeline;
use App\Services\ProjectServices;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProjectSyncService
{
    protected ?ApiClient $apiClient = null;

    public function __construct(
        protected ProjectServices $projectServices,
        protected ?ExternalEntityMappingService $mappingService = null
    ) {
        $this->mappingService = $mappingService ?? app(ExternalEntityMappingService::class);
    }

    /**
     * Set the authenticated API client for mapping operations.
     */
    public function setApiClient(?ApiClient $apiClient): self
    {
        $this->apiClient = $apiClient;

        return $this;
    }

    /**
     * Persist synchronized customer, contacts, and projects atomically.
     *
     * @param  array<string, mixed>  $resolvedPayload
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function sync(array $resolvedPayload): array
    {
        $apiClient = $this->apiClient
            ?? ($resolvedPayload['api_client'] ?? null)
            ?? (request()->attributes->has('api_client') ? request()->attributes->get('api_client') : null);

        return DB::transaction(function () use ($resolvedPayload, $apiClient) {
            $customerData = (array) ($resolvedPayload['customer'] ?? []);
            $projectsData = (array) ($resolvedPayload['projects'] ?? []);

            $customerExternalCode = $customerData['external_code'] ?? null;
            $customerExternalId = $customerData['external_id'] ?? null;
            $pmsCustomerCode = $customerData['pms_customer_code'] ?? null;

            // 1. Customer Handling
            $customer = null;
            $customerCreated = false;
            $customerMapping = null;

            if ($apiClient instanceof ApiClient) {
                $customerMapping = $this->mappingService->resolveCustomerMapping(
                    $apiClient->id,
                    $customerExternalCode,
                    $customerExternalId
                );
            }

            if ($customerMapping !== null) {
                $customer = Customer::query()->lockForUpdate()->findOrFail($customerMapping->local_id);
                $this->mappingService->validateCustomerCode($customer, $pmsCustomerCode);
                $customerCreated = false;
            } elseif (! empty($customerData['resolved']['customer_id'])) {
                $resolvedCustomerId = $customerData['resolved']['customer_id'];
                $customer = Customer::query()->lockForUpdate()->findOrFail($resolvedCustomerId);
                $customerCreated = false;

                if ($apiClient instanceof ApiClient && ($customerExternalCode !== null || $customerExternalId !== null)) {
                    $this->mappingService->createMapping(
                        $apiClient->id,
                        DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
                        $customerExternalCode,
                        $customerExternalId,
                        $customer->id
                    );
                }
            } else {
                Customer::withTrashed()->orderByDesc('id')->lockForUpdate()->first();
                $customerCode = Customer::generateCustomerCode();

                $customer = Customer::create([
                    'customer_code' => $customerCode,
                    'name' => $customerData['name'],
                    'email' => $customerData['email'] ?? null,
                    'industry_id' => $customerData['resolved']['industry_id'] ?? null,
                    'website' => $customerData['website'] ?? null,
                    'registered_country_id' => $customerData['resolved']['registered_country_id'] ?? null,
                    'emirate' => $customerData['emirate'] ?? null,
                    'company_address' => $customerData['company_address'] ?? null,
                    'sales_person_id' => $customerData['resolved']['sales_person_id'] ?? null,
                    'is_active' => $customerData['is_active'] ?? true,
                    'new_to_company' => 1,
                ]);

                $customerCreated = true;

                // Contacts Creation (only for new customers)
                if (! empty($customerData['contacts']) && is_array($customerData['contacts'])) {
                    foreach ($customerData['contacts'] as $contact) {
                        if (! is_array($contact)) {
                            continue;
                        }

                        CustomerContact::create([
                            'customer_id' => $customer->id,
                            'name' => (string) ($contact['name'] ?? ''),
                            'email' => isset($contact['email']) && $contact['email'] !== '' ? (string) $contact['email'] : null,
                            'mobile' => isset($contact['mobile']) && $contact['mobile'] !== '' ? (string) $contact['mobile'] : null,
                            'whatsapp' => isset($contact['whatsapp']) && $contact['whatsapp'] !== '' ? (string) $contact['whatsapp'] : null,
                            'landline' => isset($contact['landline']) && $contact['landline'] !== '' ? (string) $contact['landline'] : null,
                            'designation' => isset($contact['designation']) && $contact['designation'] !== '' ? (string) $contact['designation'] : null,
                            'is_primary' => (bool) ($contact['is_primary'] ?? false),
                            'is_active' => isset($contact['is_active']) ? (bool) $contact['is_active'] : true,
                        ]);
                    }
                }

                if ($apiClient instanceof ApiClient && ($customerExternalCode !== null || $customerExternalId !== null)) {
                    $this->mappingService->createMapping(
                        $apiClient->id,
                        DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
                        $customerExternalCode,
                        $customerExternalId,
                        $customer->id
                    );
                }
            }

            // 2. Projects Handling
            $processedProjects = [];

            foreach ($projectsData as $project) {
                if (! is_array($project)) {
                    continue;
                }

                $projectExternalCode = $project['external_code'] ?? null;
                $projectExternalId = $project['external_id'] ?? null;

                $projectMapping = null;
                if ($apiClient instanceof ApiClient) {
                    $projectMapping = $this->mappingService->resolveProjectMapping(
                        $apiClient->id,
                        $projectExternalCode,
                        $projectExternalId
                    );
                }

                if ($projectMapping !== null) {
                    /** @var Project $existingProject */
                    $existingProject = Project::query()->lockForUpdate()->findOrFail($projectMapping->local_id);
                    $this->mappingService->validateProjectCustomer($existingProject, (int) $customer->id);

                    $processedProjects[] = [
                        'project_id' => $existingProject->id,
                        'project_code' => $existingProject->project_code,
                        'external_code' => $projectExternalCode,
                        'external_id' => $projectExternalId,
                        'created' => false,
                    ];
                } else {
                    Project::withTrashed()->orderByDesc('id')->lockForUpdate()->first();
                    $projectCode = Project::generateProjectCode();

                    $estimatedSeconds = isset($project['estimated_time_minutes']) && $project['estimated_time_minutes'] !== null
                        ? (int) $project['estimated_time_minutes'] * 60
                        : null;
                    $customerEstimateSeconds = isset($project['customer_estimate_minutes']) && $project['customer_estimate_minutes'] !== null
                        ? (int) $project['customer_estimate_minutes'] * 60
                        : null;
                    $defaultTaskSeconds = isset($project['default_task_estimate_seconds']) && $project['default_task_estimate_seconds'] !== null
                        ? (int) $project['default_task_estimate_seconds']
                        : null;

                    $categoryIds = ! empty($project['resolved']['category_ids'])
                        ? array_values(array_unique(array_map('intval', $project['resolved']['category_ids'])))
                        : null;

                    $createdProject = Project::create([
                        'project_code' => $projectCode,
                        'name' => (string) ($project['name'] ?? ''),
                        'customer_id' => $customer->id,
                        'project_flow' => ! empty($project['project_flow']) ? (string) $project['project_flow'] : 'agile',
                        'priority' => ! empty($project['priority']) ? (string) $project['priority'] : 'medium',
                        'status_id' => $project['resolved']['project_status_id'],
                        'project_stage_id' => $project['resolved']['project_stage_id'],
                        'start_date' => $project['start_date'],
                        'end_date' => $project['end_date'],
                        'customer_end_date' => $project['customer_end_date'] ?? null,
                        'estimated_time_seconds' => $estimatedSeconds,
                        'customer_estimate_seconds' => $customerEstimateSeconds,
                        'default_task_estimate_seconds' => $defaultTaskSeconds,
                        'domain' => isset($project['domain']) && $project['domain'] !== '' ? (string) $project['domain'] : null,
                        'project_category_ids' => $categoryIds,
                        'sales_person_id' => $project['resolved']['sales_person_id'],
                        'parent_project_id' => $project['resolved']['parent_project_id'] ?? null,
                        'default_billable' => isset($project['default_billable']) ? (bool) $project['default_billable'] : true,
                        'is_active' => isset($project['is_active']) ? (bool) $project['is_active'] : true,
                    ]);

                    // Attach Technologies
                    if (! empty($project['resolved']['technology_ids']) && is_array($project['resolved']['technology_ids'])) {
                        $techIds = array_values(array_unique(array_map('intval', $project['resolved']['technology_ids'])));
                        $createdProject->technologies()->sync($techIds);
                    }

                    // Attach Service Types
                    if (! empty($project['resolved']['service_type_codes']) || ! empty($project['resolved']['service_type_ids'])) {
                        $stIds = ! empty($project['resolved']['service_type_ids']) && is_array($project['resolved']['service_type_ids'])
                            ? array_values(array_unique(array_map('intval', $project['resolved']['service_type_ids'])))
                            : [];
                        if (! empty($stIds)) {
                            $createdProject->serviceTypes()->sync($stIds);
                        }
                    }

                    // Project Status History & Stage History
                    ProjectStatusHistory::create([
                        'project_id' => $createdProject->id,
                        'status_id' => $createdProject->status_id,
                        'added_by' => null,
                    ]);

                    ProjectStageHistory::create([
                        'project_id' => $createdProject->id,
                        'stage_id' => $createdProject->project_stage_id,
                        'added_by' => null,
                    ]);

                    // Project Original Timeline
                    ProjectTimeline::create([
                        'project_id' => $createdProject->id,
                        'name' => 'Original Timeline',
                        'type' => 'original',
                        'status' => ProjectTimeline::STATUS_ACTIVE,
                        'start_date' => $createdProject->start_date,
                        'end_date' => $createdProject->end_date,
                        'customer_end_date' => $createdProject->customer_end_date,
                        'estimated_time_seconds' => $createdProject->estimated_time_seconds,
                        'customer_estimate_seconds' => $createdProject->customer_estimate_seconds,
                        'sort_order' => 1,
                        'created_by' => null,
                    ]);

                    // Project Notification Preferences
                    $this->projectServices->getNotificationDefaultsForProject($createdProject);

                    if ($apiClient instanceof ApiClient && ($projectExternalCode !== null || $projectExternalId !== null)) {
                        $this->mappingService->createMapping(
                            $apiClient->id,
                            DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
                            $projectExternalCode,
                            $projectExternalId,
                            $createdProject->id
                        );
                    }

                    $processedProjects[] = [
                        'project_id' => $createdProject->id,
                        'project_code' => $createdProject->project_code,
                        'external_code' => $projectExternalCode,
                        'external_id' => $projectExternalId,
                        'created' => true,
                    ];
                }
            }

            return [
                'customer' => [
                    'customer_id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'created' => $customerCreated,
                ],
                'projects' => $processedProjects,
            ];
        });
    }
}

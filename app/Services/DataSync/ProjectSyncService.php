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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class ProjectSyncService
{
    protected ?ApiClient $apiClient = null;

    public function __construct(
        protected ProjectServices $projectServices,
        protected ?ExternalEntityMappingService $mappingService = null,
        protected ?ProjectUpdateService $projectUpdateService = null
    ) {
        $this->mappingService = $mappingService ?? app(ExternalEntityMappingService::class);
        $this->projectUpdateService = $projectUpdateService ?? app(ProjectUpdateService::class);
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
     * Persist synchronized customer, contacts, and projects atomically (upsert).
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

            $warnings = [];

            // ==========================================
            // 1. Customer Upsert
            // ==========================================
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
                // Scenario A: Customer mapping exists
                $customer = Customer::query()->lockForUpdate()->find($customerMapping->local_id);
                if (! $customer) {
                    throw new ConflictHttpException('Mapped customer record no longer exists.');
                }

                $this->mappingService->validateCustomerCode($customer, $pmsCustomerCode);
                $this->mappingService->enrichMapping($customerMapping, $customerExternalCode, $customerExternalId);
                $customerCreated = false;

                $this->updateCustomerAttributes($customer, $customerData);
            } elseif (! empty($customerData['resolved']['customer_id'])) {
                // Scenario D: PMS customer code supplied and resolved
                $resolvedCustomerId = $customerData['resolved']['customer_id'];
                $customer = Customer::query()->lockForUpdate()->find($resolvedCustomerId);
                if (! $customer) {
                    throw new ConflictHttpException('Selected customer code does not exist.');
                }
                $customerCreated = false;

                $this->updateCustomerAttributes($customer, $customerData);

                if ($apiClient instanceof ApiClient && ($customerExternalCode !== null || $customerExternalId !== null)) {
                    $existingMapping = $this->mappingService->resolveCustomerMapping($apiClient->id, $customerExternalCode, $customerExternalId);
                    if ($existingMapping !== null) {
                        if ((int) $existingMapping->local_id !== (int) $customer->id) {
                            throw new ConflictHttpException('Conflicting external customer identifiers.');
                        }
                        $this->mappingService->enrichMapping($existingMapping, $customerExternalCode, $customerExternalId);
                    } else {
                        $this->mappingService->createMapping(
                            $apiClient->id,
                            DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
                            $customerExternalCode,
                            $customerExternalId,
                            $customer->id
                        );
                    }
                }
            } else {
                // Scenario B: Create New Customer
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

            // ==========================================
            // 2. Customer Contacts Processing
            // ==========================================
            $contactWarnings = $this->syncCustomerContacts($customer, $customerData, $customerCreated);
            if (! empty($contactWarnings)) {
                $warnings = array_merge($warnings, $contactWarnings);
            }

            // ==========================================
            // 3. Projects Upsert
            // ==========================================
            $processedProjects = [];

            foreach ($projectsData as $project) {
                if (! is_array($project)) {
                    continue;
                }

                $projectExternalCode = $project['external_code'] ?? null;
                $projectExternalId = $project['external_id'] ?? null;
                $pmsProjectCode = $project['pms_project_code'] ?? null;

                $existingProject = null;
                $projectMapping = null;

                if ($apiClient instanceof ApiClient) {
                    $projectMapping = $this->mappingService->resolveProjectMapping(
                        $apiClient->id,
                        $projectExternalCode,
                        $projectExternalId
                    );
                }

                if ($projectMapping !== null) {
                    $existingProject = Project::query()->lockForUpdate()->find($projectMapping->local_id);
                    if (! $existingProject) {
                        throw new ConflictHttpException('Mapped project record no longer exists.');
                    }
                    $this->mappingService->validateProjectCustomer($existingProject, (int) $customer->id);

                    if ($pmsProjectCode !== null && $existingProject->project_code !== $pmsProjectCode) {
                        throw new ConflictHttpException('Conflicting PMS project code.');
                    }
                } elseif (! empty($pmsProjectCode)) {
                    $existingProject = Project::query()->lockForUpdate()->where('project_code', $pmsProjectCode)->first();
                    if ($existingProject) {
                        $this->mappingService->validateProjectCustomer($existingProject, (int) $customer->id);

                        if ($apiClient instanceof ApiClient && ($projectExternalCode !== null || $projectExternalId !== null)) {
                            $mapped = $this->mappingService->resolveProjectMapping($apiClient->id, $projectExternalCode, $projectExternalId);
                            if ($mapped !== null && (int) $mapped->local_id !== (int) $existingProject->id) {
                                throw new ConflictHttpException('External mapping belongs to a different project.');
                            }
                        }
                    }
                }

                if ($existingProject !== null) {
                    // Scenario A: Update existing project
                    $updatePayload = $this->buildProjectUpdatePayload($project, $projectExternalCode, $projectExternalId);
                    $this->projectUpdateService->update($existingProject->project_code, $updatePayload, $apiClient);

                    $freshProject = $existingProject->fresh();

                    $processedProjects[] = [
                        'external_code' => $projectExternalCode,
                        'external_id' => $projectExternalId,
                        'project_code' => $freshProject->project_code,
                        'name' => $freshProject->name,
                        'operation' => 'updated',
                        'created' => false,
                    ];
                } else {
                    // Scenario B: Create new project
                    $timezone = (string) config('constants.timezone', config('app.timezone', 'UTC'));

                    Project::withTrashed()->orderByDesc('id')->lockForUpdate()->first();
                    $projectCode = Project::generateProjectCode();

                    $startDate = ! empty($project['start_date'])
                        ? Carbon::parse($project['start_date'], $timezone)->format('Y-m-d')
                        : Carbon::now($timezone)->format('Y-m-d');

                    $endDate = ! empty($project['end_date'])
                        ? Carbon::parse($project['end_date'], $timezone)->format('Y-m-d')
                        : Carbon::parse($startDate, $timezone)->addDays(30)->format('Y-m-d');

                    $customerEndDate = ! empty($project['customer_end_date'])
                        ? Carbon::parse($project['customer_end_date'], $timezone)->format('Y-m-d')
                        : null;

                    $estimatedSeconds = isset($project['estimated_time_minutes']) && $project['estimated_time_minutes'] !== null && $project['estimated_time_minutes'] !== ''
                        ? (int) $project['estimated_time_minutes'] * 60
                        : null;
                    $customerEstimateSeconds = isset($project['customer_estimate_minutes']) && $project['customer_estimate_minutes'] !== null && $project['customer_estimate_minutes'] !== ''
                        ? (int) $project['customer_estimate_minutes'] * 60
                        : null;
                    $defaultTaskSeconds = isset($project['default_task_estimate_seconds']) && $project['default_task_estimate_seconds'] !== null && $project['default_task_estimate_seconds'] !== ''
                        ? (int) $project['default_task_estimate_seconds']
                        : null;

                    $categoryIds = ! empty($project['resolved']['category_ids'])
                        ? array_values(array_unique(array_map('intval', $project['resolved']['category_ids'])))
                        : null;

                    $salesPersonId = $project['resolved']['sales_person_id'] ?? $customer->sales_person_id;

                    $createdProject = Project::create([
                        'project_code' => $projectCode,
                        'name' => (string) ($project['name'] ?? ''),
                        'customer_id' => $customer->id,
                        'project_flow' => ! empty($project['project_flow']) ? (string) $project['project_flow'] : 'agile',
                        'priority' => ! empty($project['priority']) ? (string) $project['priority'] : 'medium',
                        'status_id' => $project['resolved']['project_status_id'],
                        'project_stage_id' => $project['resolved']['project_stage_id'],
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'customer_end_date' => $customerEndDate,
                        'estimated_time_seconds' => $estimatedSeconds,
                        'customer_estimate_seconds' => $customerEstimateSeconds,
                        'default_task_estimate_seconds' => $defaultTaskSeconds,
                        'domain' => isset($project['domain']) && $project['domain'] !== '' ? (string) $project['domain'] : null,
                        'project_category_ids' => $categoryIds,
                        'sales_person_id' => $salesPersonId,
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
                    if (! empty($project['resolved']['service_type_ids']) && is_array($project['resolved']['service_type_ids'])) {
                        $stIds = array_values(array_unique(array_map('intval', $project['resolved']['service_type_ids'])));
                        if (! empty($stIds)) {
                            $createdProject->serviceTypes()->sync($stIds);
                        }
                    }

                    // Status & Stage Histories
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

                    // Original Timeline
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

                    // Notification Preferences
                    $this->projectServices->getNotificationDefaultsForProject($createdProject);

                    // Create External Mapping
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
                        'external_code' => $projectExternalCode,
                        'external_id' => $projectExternalId,
                        'project_code' => $createdProject->project_code,
                        'name' => $createdProject->name,
                        'operation' => 'created',
                        'created' => true,
                    ];
                }
            }

            // Summary
            $projectsCreatedCount = 0;
            $projectsUpdatedCount = 0;
            foreach ($processedProjects as $p) {
                if ($p['operation'] === 'created') {
                    $projectsCreatedCount++;
                } else {
                    $projectsUpdatedCount++;
                }
            }

            $response = [
                'customer' => [
                    'external_code' => $customerExternalCode,
                    'external_id' => $customerExternalId,
                    'customer_code' => $customer->customer_code,
                    'name' => $customer->name,
                    'operation' => $customerCreated ? 'created' : 'updated',
                    'created' => $customerCreated,
                ],
                'projects' => $processedProjects,
                'summary' => [
                    'customer_created' => $customerCreated ? 1 : 0,
                    'customer_updated' => $customerCreated ? 0 : 1,
                    'projects_created' => $projectsCreatedCount,
                    'projects_updated' => $projectsUpdatedCount,
                ],
            ];

            if (! empty($warnings)) {
                $response['warnings'] = $warnings;
            }

            return $response;
        });
    }

    /**
     * Update customer attributes with supplied valid fields, preserving omitted fields.
     *
     * @param  Customer  $customer
     * @param  array<string, mixed>  $customerData
     * @return void
     */
    protected function updateCustomerAttributes(Customer $customer, array $customerData): void
    {
        $updates = [];

        if (isset($customerData['name']) && trim((string) $customerData['name']) !== '' && $customer->name !== $customerData['name']) {
            $updates['name'] = (string) $customerData['name'];
        }
        if (array_key_exists('email', $customerData) && $customerData['email'] !== null && $customer->email !== $customerData['email']) {
            $updates['email'] = (string) $customerData['email'];
        }
        if (! empty($customerData['resolved']['industry_id']) && (int) $customer->industry_id !== (int) $customerData['resolved']['industry_id']) {
            $updates['industry_id'] = (int) $customerData['resolved']['industry_id'];
        }
        if (array_key_exists('website', $customerData) && $customerData['website'] !== null && $customer->website !== $customerData['website']) {
            $updates['website'] = (string) $customerData['website'];
        }
        if (! empty($customerData['resolved']['registered_country_id']) && (int) $customer->registered_country_id !== (int) $customerData['resolved']['registered_country_id']) {
            $updates['registered_country_id'] = (int) $customerData['resolved']['registered_country_id'];
        }
        if (array_key_exists('emirate', $customerData) && $customerData['emirate'] !== null && $customer->emirate !== $customerData['emirate']) {
            $updates['emirate'] = (string) $customerData['emirate'];
        }
        if (array_key_exists('company_address', $customerData) && $customerData['company_address'] !== null && $customer->company_address !== $customerData['company_address']) {
            $updates['company_address'] = (string) $customerData['company_address'];
        }
        if (! empty($customerData['resolved']['sales_person_id']) && (int) $customer->sales_person_id !== (int) $customerData['resolved']['sales_person_id']) {
            $updates['sales_person_id'] = (int) $customerData['resolved']['sales_person_id'];
        }
        if (array_key_exists('is_active', $customerData) && $customerData['is_active'] !== null && (bool) $customer->is_active !== (bool) $customerData['is_active']) {
            $updates['is_active'] = (bool) $customerData['is_active'];
        }

        if (! empty($updates)) {
            $customer->update($updates);
        }
    }

    /**
     * Process customer contacts with duplicate email handling (Level 1 & Level 2).
     *
     * @param  Customer  $customer
     * @param  array<string, mixed>  $customerData
     * @param  bool  $customerCreated
     * @return array<int, string>
     */
    protected function syncCustomerContacts(Customer $customer, array $customerData, bool $customerCreated): array
    {
        $warnings = [];
        $contacts = $customerData['contacts'] ?? [];

        if (! is_array($contacts) || empty($contacts)) {
            return $warnings;
        }

        $seenEmailsInBatch = [];
        $newPrimaryContactId = null;

        foreach ($contacts as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            // Normalize alias fields
            $landline = isset($contact['landline']) && trim((string) $contact['landline']) !== ''
                ? (string) $contact['landline']
                : (isset($contact['landline_number']) && trim((string) $contact['landline_number']) !== '' ? (string) $contact['landline_number'] : null);

            $whatsapp = isset($contact['whatsapp']) && trim((string) $contact['whatsapp']) !== ''
                ? (string) $contact['whatsapp']
                : (isset($contact['whatsapp_number']) && trim((string) $contact['whatsapp_number']) !== '' ? (string) $contact['whatsapp_number'] : null);

            $mobile = isset($contact['mobile']) && trim((string) $contact['mobile']) !== ''
                ? (string) $contact['mobile']
                : null;

            $rawEmail = isset($contact['email']) && trim((string) $contact['email']) !== ''
                ? trim((string) $contact['email'])
                : null;

            $name = (string) ($contact['name'] ?? '');
            $designation = isset($contact['designation']) && trim((string) $contact['designation']) !== ''
                ? (string) $contact['designation']
                : null;
            $isPrimary = filter_var($contact['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $isActive = isset($contact['is_active']) ? filter_var($contact['is_active'], FILTER_VALIDATE_BOOLEAN) : true;

            // Find existing contact of this customer if updating
            $matchedContact = null;
            if (! $customerCreated) {
                if ($rawEmail !== null) {
                    $matchedContact = $customer->contacts()
                        ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($rawEmail)])
                        ->first();
                }
                if (! $matchedContact && $mobile !== null) {
                    $matchedContact = $customer->contacts()
                        ->where('mobile', $mobile)
                        ->first();
                }
                if (! $matchedContact && $name !== '') {
                    $matchedContact = $customer->contacts()
                        ->where('name', $name)
                        ->first();
                }
            }

            // Duplicate email handling (Level 1)
            $effectiveEmail = $rawEmail;
            if ($rawEmail !== null) {
                $lowerEmail = strtolower($rawEmail);
                if (in_array($lowerEmail, $seenEmailsInBatch, true)) {
                    $effectiveEmail = null;
                    $warnings[] = "Duplicate email '{$rawEmail}' in request batch for contact '{$name}' normalized to null.";
                } else {
                    $conflictQuery = CustomerContact::query()
                        ->whereRaw('LOWER(TRIM(email)) = ?', [$lowerEmail]);

                    if ($matchedContact !== null) {
                        $conflictQuery->where('id', '!=', $matchedContact->id);
                    }

                    if ($conflictQuery->exists()) {
                        $effectiveEmail = null;
                        $warnings[] = "Contact email '{$rawEmail}' for '{$name}' already exists in PMS and was set to null.";
                    } else {
                        $seenEmailsInBatch[] = $lowerEmail;
                    }
                }
            }

            $contactAttributes = [
                'customer_id' => $customer->id,
                'name' => $name,
                'email' => $effectiveEmail,
                'mobile' => $mobile,
                'whatsapp' => $whatsapp,
                'landline' => $landline,
                'designation' => $designation,
                'is_primary' => $isPrimary,
                'is_active' => $isActive,
            ];

            // Persist with Level 2 protection
            $savedContact = $this->persistContact($contactAttributes, $matchedContact, $warnings);

            if ($isPrimary) {
                $newPrimaryContactId = $savedContact->id;
            }
        }

        // Reconcile primary contact flag for customer
        if ($newPrimaryContactId !== null) {
            // Clear primary flag on all other contacts of this customer
            $customer->contacts()
                ->where('id', '!=', $newPrimaryContactId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        } else {
            // Ensure at least one primary contact exists
            $hasPrimary = $customer->contacts()->where('is_primary', true)->exists();
            if (! $hasPrimary) {
                $firstContact = $customer->contacts()->orderBy('id')->first();
                if ($firstContact) {
                    $firstContact->update(['is_primary' => true]);
                }
            }
        }

        return $warnings;
    }

    /**
     * Persist contact with Level 2 concurrent uniqueness violation protection.
     *
     * @param  array<string, mixed>  $attributes
     * @param  CustomerContact|null  $existing
     * @param  array<int, string>  $warnings
     * @return CustomerContact
     *
     * @throws Throwable
     */
    protected function persistContact(array $attributes, ?CustomerContact $existing, array &$warnings): CustomerContact
    {
        try {
            if ($existing !== null) {
                $existing->update($attributes);

                return $existing;
            }

            return CustomerContact::create($attributes);
        } catch (Throwable $e) {
            if ($this->isUniqueEmailViolation($e)) {
                $warnings[] = "Concurrent duplicate email '{$attributes['email']}' resolved to null.";
                $attributes['email'] = null;

                if ($existing !== null) {
                    $existing->update($attributes);

                    return $existing;
                }

                return CustomerContact::create($attributes);
            }

            throw $e;
        }
    }

    /**
     * Detect unique email constraint violation exception.
     *
     * @param  Throwable  $e
     * @return bool
     */
    protected function isUniqueEmailViolation(Throwable $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, 'customer_contacts_email_unique')
            || (str_contains($msg, 'Duplicate entry') && str_contains($msg, 'customer_contacts'));
    }

    /**
     * Build update payload for existing project, preserving omitted fields.
     *
     * @param  array<string, mixed>  $project
     * @param  string|null  $externalCode
     * @param  string|null  $externalId
     * @return array<string, mixed>
     */
    protected function buildProjectUpdatePayload(array $project, ?string $externalCode, ?string $externalId): array
    {
        $raw = (array) ($project['raw_input'] ?? $project);
        $payload = [];

        if ($externalCode !== null) {
            $payload['external_code'] = $externalCode;
        }
        if ($externalId !== null) {
            $payload['external_id'] = $externalId;
        }
        if (isset($raw['name']) && trim((string) $raw['name']) !== '') {
            $payload['name'] = (string) $raw['name'];
        }
        if (! empty($raw['project_flow'])) {
            $payload['project_flow'] = (string) $raw['project_flow'];
        }
        if (! empty($raw['priority'])) {
            $payload['priority'] = (string) $raw['priority'];
        }
        if (! empty($raw['start_date'])) {
            $payload['start_date'] = (string) $raw['start_date'];
        }
        if (! empty($raw['end_date'])) {
            $payload['end_date'] = (string) $raw['end_date'];
        }
        if (! empty($raw['customer_end_date'])) {
            $payload['customer_end_date'] = (string) $raw['customer_end_date'];
        }
        if (isset($raw['estimated_time_minutes']) && $raw['estimated_time_minutes'] !== null && $raw['estimated_time_minutes'] !== '') {
            $payload['estimated_time_minutes'] = (int) $raw['estimated_time_minutes'];
        }
        if (isset($raw['customer_estimate_minutes']) && $raw['customer_estimate_minutes'] !== null && $raw['customer_estimate_minutes'] !== '') {
            $payload['customer_estimate_minutes'] = (int) $raw['customer_estimate_minutes'];
        }
        if (isset($raw['default_task_estimate_seconds']) && $raw['default_task_estimate_seconds'] !== null && $raw['default_task_estimate_seconds'] !== '') {
            $payload['default_task_estimate_seconds'] = (int) $raw['default_task_estimate_seconds'];
        }
        if (isset($raw['domain']) && trim((string) $raw['domain']) !== '') {
            $payload['domain'] = (string) $raw['domain'];
        }
        if (! empty($raw['sales_person_email'])) {
            $payload['sales_person_email'] = (string) $raw['sales_person_email'];
        }
        if (! empty($raw['parent_project_code'])) {
            $payload['parent_project_code'] = (string) $raw['parent_project_code'];
        }
        if (! empty($raw['status_code'])) {
            $payload['status_code'] = (string) $raw['status_code'];
        }
        if (! empty($raw['stage_code'])) {
            $payload['stage_code'] = (string) $raw['stage_code'];
        }
        if (! empty($raw['category_codes']) && is_array($raw['category_codes'])) {
            $payload['category_codes'] = $raw['category_codes'];
        }
        if (! empty($raw['technology_codes']) && is_array($raw['technology_codes'])) {
            $payload['technology_codes'] = $raw['technology_codes'];
        }
        if (! empty($raw['service_type_codes']) && is_array($raw['service_type_codes'])) {
            $payload['service_type_codes'] = $raw['service_type_codes'];
        }
        if (array_key_exists('default_billable', $raw) && $raw['default_billable'] !== null) {
            $payload['default_billable'] = (bool) $raw['default_billable'];
        }
        if (array_key_exists('is_active', $raw) && $raw['is_active'] !== null) {
            $payload['is_active'] = (bool) $raw['is_active'];
        }

        return $payload;
    }
}

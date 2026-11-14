<?php

namespace App\Services\DataSync;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\DataSyncEntityMapping;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class RecordMappingService
{
    /**
     * Default records per page.
     */
    public const DEFAULT_PER_PAGE = 25;

    /**
     * Supported entity type filter options.
     *
     * @var array<string, string>
     */
    public const ENTITY_TYPES = [
        DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER => 'Customer',
        DataSyncEntityMapping::ENTITY_TYPE_PROJECT => 'Project',
    ];

    /**
     * Retrieve paginated and filtered record mappings with resolved PMS entities.
     *
     * @param  array{api_client_id?: int|string|null, entity_type?: string|null, search?: string|null}  $filters
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getMappings(array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = max(5, min(500, $perPage));

        $query = DataSyncEntityMapping::query()->with('apiClient');

        // Filter by API Client ID
        if (!empty($filters['api_client_id'])) {
            $query->where('api_client_id', (int) $filters['api_client_id']);
        }

        // Filter by Entity Type
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', trim((string) $filters['entity_type']));
        }

        // Search conditions
        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('external_code', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere(function ($sub) use ($search) {
                        $sub->where('entity_type', DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER)
                            ->whereIn('local_id', function ($cq) use ($search) {
                                $cq->select('id')
                                    ->from('customers')
                                    ->where(function ($customerQuery) use ($search) {
                                        $customerQuery->where('customer_code', 'like', "%{$search}%")
                                            ->orWhere('name', 'like', "%{$search}%");
                                    });
                            });
                    })
                    ->orWhere(function ($sub) use ($search) {
                        $sub->where('entity_type', DataSyncEntityMapping::ENTITY_TYPE_PROJECT)
                            ->whereIn('local_id', function ($pq) use ($search) {
                                $pq->select('id')
                                    ->from('projects')
                                    ->where(function ($projectQuery) use ($search) {
                                        $projectQuery->where('project_code', 'like', "%{$search}%")
                                            ->orWhere('name', 'like', "%{$search}%");
                                    });
                            });
                    });
            });
        }

        // Paginate latest records first
        $paginator = $query->latest('id')->paginate($perPage)->withQueryString();

        // Query Optimization: Collect IDs and batch-fetch PMS entities to avoid N+1 queries
        $customerIds = $paginator->getCollection()
            ->where('entity_type', DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER)
            ->pluck('local_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $projectIds = $paginator->getCollection()
            ->where('entity_type', DataSyncEntityMapping::ENTITY_TYPE_PROJECT)
            ->pluck('local_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $customers = !empty($customerIds)
            ? Customer::withTrashed()->whereIn('id', $customerIds)->get()->keyBy('id')
            : collect();

        $projects = !empty($projectIds)
            ? Project::withTrashed()->whereIn('id', $projectIds)->get()->keyBy('id')
            : collect();

        // Decorate paginated collection with resolved PMS and client details
        $paginator->getCollection()->transform(function (DataSyncEntityMapping $mapping) use ($customers, $projects) {
            $entityType = $mapping->entity_type;
            $localId = $mapping->local_id;

            if ($entityType === DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER) {
                /** @var Customer|null $customer */
                $customer = $customers->get($localId);
                if ($customer) {
                    $mapping->pms_code = $customer->customer_code;
                    $mapping->pms_name = $customer->name;
                    $mapping->is_pms_deleted = $customer->trashed();
                    $mapping->is_pms_missing = false;
                } else {
                    $mapping->pms_code = null;
                    $mapping->pms_name = null;
                    $mapping->is_pms_deleted = false;
                    $mapping->is_pms_missing = true;
                }
            } elseif ($entityType === DataSyncEntityMapping::ENTITY_TYPE_PROJECT) {
                /** @var Project|null $project */
                $project = $projects->get($localId);
                if ($project) {
                    $mapping->pms_code = $project->project_code;
                    $mapping->pms_name = $project->name;
                    $mapping->is_pms_deleted = $project->trashed();
                    $mapping->is_pms_missing = false;
                } else {
                    $mapping->pms_code = null;
                    $mapping->pms_name = null;
                    $mapping->is_pms_deleted = false;
                    $mapping->is_pms_missing = true;
                }
            } else {
                $mapping->pms_code = null;
                $mapping->pms_name = null;
                $mapping->is_pms_deleted = false;
                $mapping->is_pms_missing = true;
            }

            // Resolve API Client details safely
            $client = $mapping->apiClient;
            if ($client) {
                $mapping->client_name = $client->name;
                $mapping->is_client_active = (bool) $client->is_active;
                $mapping->is_client_deleted = $client->trashed();
            } else {
                $mapping->client_name = 'Unknown Client';
                $mapping->is_client_active = false;
                $mapping->is_client_deleted = true;
            }

            return $mapping;
        });

        return $paginator;
    }

    /**
     * Get API Client options for filter dropdown (including inactive and soft-deleted).
     *
     * @return Collection<int, ApiClient>
     */
    public function getApiClientFilterOptions(): Collection
    {
        return ApiClient::withTrashed()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'deleted_at']);
    }

    /**
     * Get Entity Type options for filter dropdown.
     *
     * @return array<string, string>
     */
    public function getEntityTypeOptions(): array
    {
        return self::ENTITY_TYPES;
    }
}


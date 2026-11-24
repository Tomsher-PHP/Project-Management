<?php

namespace App\Services\DataSync;

use App\Models\ApiClient;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DataSyncEntityMapping;
use App\Models\DataSyncOutboundConnection;
use App\Models\Industry;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\MeetingStatus;
use App\Models\MeetingType;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class OutboundPayloadService
{
    /**
     * Build the outbound JSON payload for a given module, operation, and model.
     *
     * @param  string  $module
     * @param  string  $operation
     * @param  Model  $model
     * @param  DataSyncOutboundConnection|null  $connection
     * @return array<string, mixed>
     */
    public function buildPayload(string $module, string $operation, Model $model, ?DataSyncOutboundConnection $connection = null): array
    {
        if ($operation === 'delete') {
            return $this->buildDeletePayload($module, $model, $connection);
        }

        return match ($module) {
            'technologies' => $this->buildTechnologyPayload($model),
            'industries' => $this->buildIndustryPayload($model),
            'project_categories' => $this->buildProjectCategoryPayload($model),
            'project_statuses' => $this->buildProjectStatusPayload($model),
            'customers' => $this->buildCustomerPayload($model, $connection),
            'projects' => $this->buildProjectPayload($model, $connection),
            'meetings' => $this->buildMeetingPayload($model),
            default => throw new InvalidArgumentException("Unsupported module [{$module}] for outbound payload generation."),
        };
    }

    /**
     * Build deletion payload containing the stable identifier for the module.
     *
     * @param  string  $module
     * @param  Model  $model
     * @param  DataSyncOutboundConnection|null  $connection
     * @return array<string, mixed>
     */
    public function buildDeletePayload(string $module, Model $model, ?DataSyncOutboundConnection $connection = null): array
    {
        return match ($module) {
            'technologies' => [
                'module' => 'technology',
                'code' => (string) $model->code,
            ],
            'industries' => [
                'module' => 'industries',
                'code' => (string) $model->code,
            ],
            'project_categories' => [
                'module' => 'project_categories',
                'code' => (string) $model->code,
            ],
            'project_statuses' => [
                'module' => 'project_statuses',
                'code' => (string) $model->code,
            ],
            'customers' => $this->buildCustomerDeletePayload($model, $connection),
            'projects' => $this->buildProjectDeletePayload($model, $connection),
            'meetings' => [
                'id' => (int) $model->id,
                'title' => (string) $model->title,
            ],
            default => throw new InvalidArgumentException("Unsupported module [{$module}] for delete payload generation."),
        };
    }

    /**
     * Build payload for Technology.
     */
    protected function buildTechnologyPayload(Technology|Model $technology): array
    {
        return [
            'module' => 'technologies',
            'code' => (string) $technology->code,
            'name' => (string) $technology->name,
            'sort_order' => (int) $technology->sort_order,
            'is_active' => (bool) $technology->is_active,
        ];
    }

    /**
     * Build payload for Industry, supporting parent relationships via parent_code.
     */
    protected function buildIndustryPayload(Industry|Model $industry): array
    {
        $parentCode = null;
        if ($industry->relationLoaded('parent') && $industry->parent) {
            $parentCode = $industry->parent->code ? (string) $industry->parent->code : null;
        } elseif (! empty($industry->parent_id)) {
            $parent = Industry::withTrashed()->find($industry->parent_id);
            $parentCode = $parent?->code ? (string) $parent->code : null;
        }

        return [
            'module' => 'industries',
            'code' => (string) $industry->code,
            'name' => (string) $industry->name,
            'parent_code' => $parentCode,
            'sort_order' => (int) $industry->sort_order,
            'is_active' => (bool) $industry->is_active,
        ];
    }

    /**
     * Build payload for ProjectCategory.
     */
    protected function buildProjectCategoryPayload(ProjectCategory|Model $category): array
    {
        return [
            'module' => 'project_categories',
            'code' => (string) $category->code,
            'name' => (string) $category->name,
            'sort_order' => (int) $category->sort_order,
            'is_active' => (bool) $category->is_active,
        ];
    }

    /**
     * Build payload for ProjectStatus preserving status attributes.
     */
    protected function buildProjectStatusPayload(ProjectStatus|Model $status): array
    {
        return [
            'module' => 'project_statuses',
            'code' => (string) $status->code,
            'name' => (string) $status->name,
            'color' => $status->color,
            'type' => $status->type,
            'sort_order' => (int) $status->sort_order,
            'is_default' => (bool) $status->is_default,
            'is_active' => (bool) $status->is_active,
        ];
    }

    /**
     * Build payload for Customer.
     */
    protected function buildCustomerPayload(Customer|Model $customer, ?DataSyncOutboundConnection $connection = null): array
    {
        $industryCode = null;
        if ($customer->relationLoaded('industry') && $customer->industry) {
            $industryCode = $customer->industry->code;
        } elseif (! empty($customer->industry_id)) {
            $industryCode = Industry::withTrashed()->where('id', $customer->industry_id)->value('code');
        }

        $countryCode = null;
        if ($customer->relationLoaded('country') && $customer->country) {
            $countryCode = $customer->country->code;
        } elseif (! empty($customer->registered_country_id)) {
            $country = Country::find($customer->registered_country_id);
            $countryCode = $country?->code;
        }

        $salesPersonEmail = null;
        if ($customer->relationLoaded('salesPerson') && $customer->salesPerson) {
            $salesPersonEmail = $customer->salesPerson->email;
        } elseif (! empty($customer->sales_person_id)) {
            $salesPersonEmail = User::withTrashed()->where('id', $customer->sales_person_id)->value('email');
        }

        $payload = [
            'customer_code' => (string) $customer->customer_code,
            'name' => (string) $customer->name,
            'email' => $customer->email,
            'industry_code' => $industryCode,
            'website' => $customer->website,
            'country_code' => $countryCode,
            'emirate' => $customer->emirate,
            'company_address' => $customer->company_address,
            'sales_person_email' => $salesPersonEmail,
            'is_active' => (bool) $customer->is_active,
        ];

        // Include external mapping identifiers if established
        $mapping = $this->resolveEntityMapping(DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER, (int) $customer->id, $connection);
        if ($mapping) {
            if ($mapping->external_code !== null) {
                $payload['external_code'] = $mapping->external_code;
            }
            if ($mapping->external_id !== null) {
                $payload['external_id'] = $mapping->external_id;
            }
        }

        // Include contacts if loaded or available
        $contactsQuery = $customer->relationLoaded('contacts') ? $customer->contacts : $customer->contacts()->get();
        if ($contactsQuery->isNotEmpty()) {
            $payload['contacts'] = $contactsQuery->map(function ($contact) {
                return [
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'mobile' => $contact->mobile,
                    'whatsapp' => $contact->whatsapp,
                    'landline' => $contact->landline,
                    'designation' => $contact->designation,
                    'is_primary' => (bool) $contact->is_primary,
                    'is_active' => (bool) $contact->is_active,
                ];
            })->values()->all();
        }

        return $payload;
    }

    /**
     * Build deletion payload for Customer.
     */
    protected function buildCustomerDeletePayload(Customer|Model $customer, ?DataSyncOutboundConnection $connection = null): array
    {
        $payload = [
            'customer_code' => (string) $customer->customer_code,
        ];

        $mapping = $this->resolveEntityMapping(DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER, (int) $customer->id, $connection);
        if ($mapping) {
            if ($mapping->external_code !== null) {
                $payload['external_code'] = $mapping->external_code;
            }
            if ($mapping->external_id !== null) {
                $payload['external_id'] = $mapping->external_id;
            }
        }

        return $payload;
    }

    /**
     * Build payload for Project.
     */
    protected function buildProjectPayload(Project|Model $project, ?DataSyncOutboundConnection $connection = null): array
    {
        $customerCode = null;
        if ($project->relationLoaded('customer') && $project->customer) {
            $customerCode = $project->customer->customer_code;
        } elseif (! empty($project->customer_id)) {
            $customerCode = Customer::withTrashed()->where('id', $project->customer_id)->value('customer_code');
        }

        $statusCode = null;
        if ($project->relationLoaded('projectStatus') && $project->projectStatus) {
            $statusCode = $project->projectStatus->code;
        } elseif (! empty($project->status_id)) {
            $statusCode = ProjectStatus::withTrashed()->where('id', $project->status_id)->value('code');
        }

        $stageCode = null;
        if ($project->relationLoaded('projectStage') && $project->projectStage) {
            $stageCode = $project->projectStage->code;
        } elseif (! empty($project->project_stage_id)) {
            $stageCode = ProjectStage::withTrashed()->where('id', $project->project_stage_id)->value('code');
        }

        $salesPersonEmail = null;
        if ($project->relationLoaded('salesPerson') && $project->salesPerson) {
            $salesPersonEmail = $project->salesPerson->email;
        } elseif (! empty($project->sales_person_id)) {
            $salesPersonEmail = User::withTrashed()->where('id', $project->sales_person_id)->value('email');
        }

        $parentProjectCode = null;
        if ($project->relationLoaded('parentProject') && $project->parentProject) {
            $parentProjectCode = $project->parentProject->project_code;
        } elseif (! empty($project->parent_project_id)) {
            $parentProjectCode = Project::withTrashed()->where('id', $project->parent_project_id)->value('project_code');
        }

        $categoryCodes = [];
        if (! empty($project->project_category_ids) && is_array($project->project_category_ids)) {
            $categoryCodes = ProjectCategory::withTrashed()
                ->whereIn('id', $project->project_category_ids)
                ->pluck('code')
                ->filter()
                ->values()
                ->all();
        }

        $technologyCodes = [];
        $technologiesRelation = $project->relationLoaded('technologies') ? $project->technologies : $project->technologies()->get();
        if ($technologiesRelation->isNotEmpty()) {
            $technologyCodes = $technologiesRelation->pluck('code')->filter()->values()->all();
        }

        $estimatedMinutes = $project->estimated_time_seconds !== null
            ? round($project->estimated_time_seconds / 60, 2)
            : null;

        $customerEstimateMinutes = $project->customer_estimate_seconds !== null
            ? round($project->customer_estimate_seconds / 60, 2)
            : null;

        $payload = [
            'project_code' => (string) $project->project_code,
            'name' => (string) $project->name,
            'customer_code' => $customerCode,
            'project_flow' => $project->project_flow,
            'priority' => $project->priority,
            'status_code' => $statusCode,
            'stage_code' => $stageCode,
            'start_date' => $project->start_date ? Carbon::parse($project->start_date)->format('Y-m-d') : null,
            'end_date' => $project->end_date ? Carbon::parse($project->end_date)->format('Y-m-d') : null,
            'customer_end_date' => $project->customer_end_date ? Carbon::parse($project->customer_end_date)->format('Y-m-d') : null,
            'estimated_time_minutes' => $estimatedMinutes,
            'customer_estimate_minutes' => $customerEstimateMinutes,
            'default_task_estimate_seconds' => $project->default_task_estimate_seconds,
            'domain' => $project->domain,
            'category_codes' => $categoryCodes,
            'technology_codes' => $technologyCodes,
            'default_billable' => (bool) $project->default_billable,
            'is_active' => (bool) $project->is_active,
            'sales_person_email' => $salesPersonEmail,
            'parent_project_code' => $parentProjectCode,
        ];

        // Include external mapping identifiers if established
        $mapping = $this->resolveEntityMapping(DataSyncEntityMapping::ENTITY_TYPE_PROJECT, (int) $project->id, $connection);
        if ($mapping) {
            if ($mapping->external_code !== null) {
                $payload['external_code'] = $mapping->external_code;
            }
            if ($mapping->external_id !== null) {
                $payload['external_id'] = $mapping->external_id;
            }
        }

        return $payload;
    }

    /**
     * Build deletion payload for Project.
     */
    protected function buildProjectDeletePayload(Project|Model $project, ?DataSyncOutboundConnection $connection = null): array
    {
        $payload = [
            'project_code' => (string) $project->project_code,
        ];

        $mapping = $this->resolveEntityMapping(DataSyncEntityMapping::ENTITY_TYPE_PROJECT, (int) $project->id, $connection);
        if ($mapping) {
            if ($mapping->external_code !== null) {
                $payload['external_code'] = $mapping->external_code;
            }
            if ($mapping->external_id !== null) {
                $payload['external_id'] = $mapping->external_id;
            }
        }

        return $payload;
    }

    /**
     * Build payload for Meeting.
     */
    protected function buildMeetingPayload(Meeting|Model $meeting): array
    {
        $projectCode = null;
        if ($meeting->relationLoaded('project') && $meeting->project) {
            $projectCode = $meeting->project->project_code;
        } elseif (! empty($meeting->project_id)) {
            $projectCode = Project::withTrashed()->where('id', $meeting->project_id)->value('project_code');
        }

        $statusData = null;
        $statusModel = $meeting->relationLoaded('meetingStatus') ? $meeting->meetingStatus : null;
        if (! $statusModel && ! empty($meeting->meeting_status_id)) {
            $statusModel = MeetingStatus::find($meeting->meeting_status_id);
        }
        if ($statusModel) {
            $statusData = [
                'code' => $statusModel->code ?? null,
                'name' => $statusModel->name ?? null,
            ];
        }

        $meetingTypeName = null;
        $typeModel = $meeting->relationLoaded('meetingType') ? $meeting->meetingType : null;
        if (! $typeModel && ! empty($meeting->meeting_type_id)) {
            $typeModel = MeetingType::find($meeting->meeting_type_id);
        }
        if ($typeModel) {
            $meetingTypeName = $typeModel->name ?? null;
        }

        $locationName = null;
        $locationModel = $meeting->relationLoaded('meetingLocation') ? $meeting->meetingLocation : null;
        if (! $locationModel && ! empty($meeting->meeting_location_id)) {
            $locationModel = MeetingLocation::find($meeting->meeting_location_id);
        }
        if ($locationModel) {
            $locationName = $locationModel->name ?? null;
        }

        $organizerEmail = null;
        if ($meeting->relationLoaded('organizer') && $meeting->organizer) {
            $organizerEmail = $meeting->organizer->email;
        } elseif (! empty($meeting->organizer_id)) {
            $organizerEmail = User::withTrashed()->where('id', $meeting->organizer_id)->value('email');
        }

        return [
            'id' => (int) $meeting->id,
            'title' => (string) $meeting->title,
            'description' => $meeting->description,
            'start_at' => $meeting->start_at ? Carbon::parse($meeting->start_at)->toIso8601String() : null,
            'end_at' => $meeting->end_at ? Carbon::parse($meeting->end_at)->toIso8601String() : null,
            'url' => $meeting->url,
            'location_details' => $meeting->location_details,
            'project_code' => $projectCode,
            'status' => $statusData,
            'meeting_type' => $meetingTypeName,
            'location' => $locationName,
            'organizer_email' => $organizerEmail,
        ];
    }

    /**
     * Resolve existing entity mapping for a given entity type and local ID.
     */
    protected function resolveEntityMapping(string $entityType, int $localId, ?DataSyncOutboundConnection $connection = null): ?DataSyncEntityMapping
    {
        $query = DataSyncEntityMapping::query()
            ->where('entity_type', $entityType)
            ->where('local_id', $localId);

        // If connection name matches an ApiClient, prioritize that mapping
        if ($connection) {
            $matchingClient = ApiClient::where('name', $connection->name)->first();
            if ($matchingClient) {
                $mappedForClient = (clone $query)->where('api_client_id', $matchingClient->id)->first();
                if ($mappedForClient) {
                    return $mappedForClient;
                }
            }
        }

        return $query->first();
    }
}

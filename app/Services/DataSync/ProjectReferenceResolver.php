<?php

namespace App\Services\DataSync;

use App\Models\Country;
use App\Models\Customer;
use App\Models\Industry;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\ServiceType;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ProjectReferenceResolver
{
    /**
     * Resolve database references for normalized project sync payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function resolve(array $payload): array
    {
        $errors = [];
        $customer = (array) ($payload['customer'] ?? []);

        // 1. Customer Resolution
        $resolvedCustomerId = null;
        $resolvedCustomerModel = null;
        $pmsCustomerCode = $customer['pms_customer_code'] ?? null;

        if (! empty($pmsCustomerCode)) {
            $existingCustomer = Customer::query()->where('customer_code', $pmsCustomerCode)->first();
            if ($existingCustomer) {
                $resolvedCustomerId = (int) $existingCustomer->id;
                $resolvedCustomerModel = $existingCustomer;
            } else {
                $errors['customer.pms_customer_code'] = ['The selected customer code does not exist.'];
            }
        }

        // 2. Customer Industry Resolution
        $resolvedIndustryId = null;
        if (! empty($customer['industry_code'])) {
            $industry = Industry::query()->where('code', (string) $customer['industry_code'])->first();
            if ($industry) {
                $resolvedIndustryId = (int) $industry->id;
            } else {
                $errors['customer.industry_code'] = ['The selected industry code does not exist.'];
            }
        }

        // 3. Customer Country Resolution
        $resolvedCountryId = null;
        if (! empty($customer['country_code'])) {
            $country = Country::query()->where('code', strtoupper((string) $customer['country_code']))->first();
            if ($country) {
                $resolvedCountryId = (int) $country->id;
            } else {
                $errors['customer.country_code'] = ['The selected country code does not exist.'];
            }
        }

        // 4. Customer Salesperson Resolution
        $resolvedCustomerSalesPersonId = null;
        if (! empty($customer['sales_person_email'])) {
            $salesUser = User::query()->where('email', (string) $customer['sales_person_email'])->first();
            if ($salesUser) {
                $resolvedCustomerSalesPersonId = (int) $salesUser->id;
            } else {
                $errors['customer.sales_person_email'] = ['The selected customer sales person email does not exist.'];
            }
        }

        // 5. Projects References Resolution
        $resolvedProjects = [];
        $projects = (array) ($payload['projects'] ?? []);

        foreach ($projects as $index => $project) {
            if (! is_array($project)) {
                continue;
            }

            // Project Status: specified -> default -> error if missing default
            $status = null;
            if (! empty($project['status_code'])) {
                $status = ProjectStatus::query()->where('code', (string) $project['status_code'])->first();
            }
            if (! $status) {
                $status = ProjectStatus::query()->where('is_default', 1)->first();
            }
            if (! $status) {
                $errors["projects.{$index}.status_code"] = ['Default project status is not configured.'];
            }

            // Project Stage: specified -> default -> error if missing default
            $stage = null;
            if (! empty($project['stage_code'])) {
                $stage = ProjectStage::query()->where('code', (string) $project['stage_code'])->first();
            }
            if (! $stage) {
                $stage = ProjectStage::query()->where('is_default', 1)->first();
            }
            if (! $stage) {
                $errors["projects.{$index}.stage_code"] = ['Default project stage is not configured.'];
            }

            // Technologies: flexible resolution, invalid ignored
            $technologyIds = [];
            if (! empty($project['technology_codes']) && is_array($project['technology_codes'])) {
                $codes = array_values(array_filter(array_map('strval', $project['technology_codes'])));
                if (! empty($codes)) {
                    $technologyIds = Technology::query()
                        ->whereIn('code', $codes)
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->all();
                }
            }

            // Categories: flexible resolution, invalid ignored
            $categoryIds = [];
            if (! empty($project['category_codes']) && is_array($project['category_codes'])) {
                $codes = array_values(array_filter(array_map('strval', $project['category_codes'])));
                if (! empty($codes)) {
                    $categoryIds = ProjectCategory::query()
                        ->whereIn('code', $codes)
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->all();
                }
            }

            // Service Types: flexible resolution, invalid ignored
            $serviceTypeIds = [];
            if (! empty($project['service_type_codes']) && is_array($project['service_type_codes'])) {
                $codes = array_values(array_filter(array_map('strval', $project['service_type_codes'])));
                if (! empty($codes)) {
                    $serviceTypeIds = ServiceType::query()
                        ->whereIn('code', $codes)
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->all();
                }
            }

            // Project Salesperson
            $resolvedProjectSalesPersonId = null;
            if (! empty($project['sales_person_email'])) {
                $projectSalesUser = User::query()->where('email', (string) $project['sales_person_email'])->first();
                if ($projectSalesUser) {
                    $resolvedProjectSalesPersonId = (int) $projectSalesUser->id;
                } else {
                    $errors["projects.{$index}.sales_person_email"] = ['The selected project sales person email does not exist.'];
                }
            }

            // Parent Project
            $resolvedParentProjectId = null;
            if (! empty($project['parent_project_code'])) {
                $parent = Project::query()->where('project_code', (string) $project['parent_project_code'])->first();
                if ($parent) {
                    $resolvedParentProjectId = (int) $parent->id;
                } else {
                    $errors["projects.{$index}.parent_project_code"] = ['The selected parent project code does not exist.'];
                }
            }

            $resolvedProjects[] = array_merge($project, [
                'resolved' => [
                    'project_status_id' => $status?->id,
                    'project_status' => $status,
                    'project_stage_id' => $stage?->id,
                    'project_stage' => $stage,
                    'technology_ids' => $technologyIds,
                    'category_ids' => $categoryIds,
                    'service_type_ids' => $serviceTypeIds,
                    'sales_person_id' => $resolvedProjectSalesPersonId,
                    'parent_project_id' => $resolvedParentProjectId,
                ],
            ]);
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'customer' => array_merge($customer, [
                'resolved' => [
                    'customer_id' => $resolvedCustomerId,
                    'customer' => $resolvedCustomerModel,
                    'industry_id' => $resolvedIndustryId,
                    'registered_country_id' => $resolvedCountryId,
                    'sales_person_id' => $resolvedCustomerSalesPersonId,
                ],
            ]),
            'projects' => $resolvedProjects,
        ];
    }
}


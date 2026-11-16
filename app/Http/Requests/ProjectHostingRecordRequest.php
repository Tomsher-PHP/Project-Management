<?php

namespace App\Http\Requests;

use App\Models\ProjectHostingRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectHostingRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');
        if (! $project) {
            return false;
        }

        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        if (! $user->can('view', $project)) {
            return false;
        }

        if ($this->isMethod('POST')) {
            return $user->can('project_hosting.create') && $user->can('update', $project);
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user->can('project_hosting.edit') && $user->can('update', $project);
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'hosting_provider_id' => [
                'required',
                'integer',
                Rule::exists('hosting_providers', 'id')->whereNull('deleted_at'),
            ],
            'hosting_service_id' => [
                'required',
                'integer',
                Rule::exists('hosting_services', 'id')->whereNull('deleted_at'),
            ],
            'hosting_service_status_id' => [
                'required',
                'integer',
                Rule::exists('hosting_service_statuses', 'id')->whereNull('deleted_at'),
            ],
            'hosting_billing_cycle_id' => [
                'nullable',
                'integer',
                Rule::exists('hosting_billing_cycles', 'id')->whereNull('deleted_at'),
            ],
            'hosting_environment_id' => [
                'nullable',
                'integer',
                Rule::exists('hosting_environments', 'id')->whereNull('deleted_at'),
            ],
            'ownership_type' => [
                'required',
                'string',
                Rule::in(array_keys(ProjectHostingRecord::OWNERSHIP_TYPES)),
            ],
            'domain' => [
                'nullable',
                'string',
                'max:255',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'next_renewal_date' => [
                'nullable',
                'date',
            ],
            'billing_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'currency_code' => [
                'nullable',
                'string',
                'size:3',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'service name',
            'hosting_provider_id' => 'hosting provider',
            'hosting_service_id' => 'hosting service',
            'hosting_service_status_id' => 'service status',
            'hosting_billing_cycle_id' => 'billing cycle',
            'hosting_environment_id' => 'environment',
            'ownership_type' => 'ownership type',
            'start_date' => 'start date',
            'expiry_date' => 'expiry date',
            'next_renewal_date' => 'next renewal date',
            'billing_amount' => 'billing amount',
            'currency_code' => 'currency',
        ];
    }
}

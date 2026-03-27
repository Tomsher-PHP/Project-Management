<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // $projectId = $this->route('project')?->id;

        $rules = [
            'name' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'priority' => 'required|in:urgent,high,medium,low',
            'project_status' => 'required|exists:project_statuses,id',
            'start_date' => 'nullable|date',
        ];

        if ($this->isMethod('POST')) {
            $rules['project_type'] = 'required|in:agile,linear';
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules += [
                'internal_end_date' => 'nullable|date|after_or_equal:start_date',
                'client_end_date' => 'nullable|date|after_or_equal:internal_end_date',
                'estimated_time_hrs' => 'nullable|integer',
                'domain' => 'nullable|string',
                'sales_person_id' => 'nullable|exists:users,id',
                'project_stage' => 'nullable|string',
                'project_category_id' => 'nullable|exists:project_categories,id',
                'default_billable' => 'nullable|boolean',
                'project_technology_ids' => 'nullable|array',
                'project_technology_ids.*' => 'nullable|exists:technologies,id',
            ];
        }

        return $rules;
    }
}

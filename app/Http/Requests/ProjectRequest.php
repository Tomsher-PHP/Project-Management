<?php

namespace App\Http\Requests;

use App\Models\Project;
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
        $rules = [
            'name' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'priority' => 'required|in:urgent,high,medium,low',
            'start_date' => 'nullable|date',
        ];

        if ($this->isMethod('POST')) {
            $rules['project_flow'] = 'required|in:agile,linear';
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules += [
                'parent_project_id' => 'nullable|exists:projects,id',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'customer_end_date' => 'nullable|date|after_or_equal:end_date',
                'estimated_time_minutes' => 'nullable|integer|min:0',
                'default_task_estimate_minutes' => 'nullable|integer|min:0',
                'domain' => 'nullable|string',
                'sales_person_id' => 'nullable|exists:users,id',
                'project_category_id' => 'nullable|exists:project_categories,id',
                'default_billable' => 'nullable|boolean',
                'project_technology_ids' => 'nullable|array',
                'project_technology_ids.*' => 'nullable|exists:technologies,id',
            ];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('parent_project_id')) {
                return;
            }

            $parentProjectId = (int) $this->input('parent_project_id');
            $currentProject = $this->route('project');

            if ($currentProject instanceof Project && $parentProjectId === (int) $currentProject->id) {
                $validator->errors()->add('parent_project_id', 'A project cannot be its own parent project.');

                return;
            }

            $selectedParentProject = Project::withTrashed()
                ->with('projectStatus:id,is_completed')
                ->find($parentProjectId);

            if (! $selectedParentProject) {
                return;
            }

            $isExistingParentSelection = $currentProject instanceof Project
                && (int) ($currentProject->parent_project_id ?? 0) === $parentProjectId;

            if ($isExistingParentSelection) {
                return;
            }

            if ($selectedParentProject->trashed() || ! $selectedParentProject->projectStatus?->is_completed) {
                $validator->errors()->add(
                    'parent_project_id',
                    'Please select a completed project when linking this project as rework or follow-up work.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a project name.',
            'name.max' => 'The project name may not be greater than 255 characters.',
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer is invalid.',
            'project_flow.required' => 'Please choose a project flow.',
            'project_flow.in' => 'The selected project flow is invalid.',
            'priority.required' => 'Please choose a priority.',
            'priority.in' => 'The selected priority is invalid.',
            'parent_project_id.exists' => 'The selected parent project is invalid.',
            'project_status.required' => 'Please choose a project status.',
            'project_status.exists' => 'The selected project status is invalid.',
            'start_date.date' => 'Please enter a valid start date.',
            'end_date.date' => 'Please enter a valid end date.',
            'end_date.after_or_equal' => 'The end date must be the same as or after the start date.',
            'customer_end_date.date' => 'Please enter a valid customer end date.',
            'customer_end_date.after_or_equal' => 'The customer end date must be the same as or after the end date.',
            'estimated_time_minutes.integer' => 'Estimate time must be a whole number of minutes.',
            'estimated_time_minutes.min' => 'Estimate time cannot be less than 0 minutes.',
            'default_task_estimate_minutes.integer' => 'Default task estimate must be a whole number of minutes.',
            'default_task_estimate_minutes.min' => 'Default task estimate cannot be less than 0 minutes.',
            'sales_person_id.exists' => 'The selected sales person is invalid.',
            'project_category_id.exists' => 'The selected project category is invalid.',
            'default_billable.boolean' => 'The default billable value is invalid.',
            'project_technology_ids.array' => 'Project technologies must be provided as a list.',
            'project_technology_ids.*.exists' => 'One or more selected technologies are invalid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'project name',
            'customer_id' => 'customer',
            'project_flow' => 'project flow',
            'priority' => 'priority',
            'parent_project_id' => 'parent project',
            'project_status' => 'project status',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'customer_end_date' => 'customer end date',
            'estimated_time_minutes' => 'estimate time',
            'default_task_estimate_minutes' => 'default task estimate',
            'domain' => 'domain',
            'sales_person_id' => 'sales person',
            'project_category_id' => 'project category',
            'default_billable' => 'default billable',
            'project_technology_ids' => 'project technologies',
        ];
    }
}

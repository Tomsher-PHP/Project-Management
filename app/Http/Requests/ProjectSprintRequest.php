<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_module_id' => ['required', 'integer', 'exists:project_modules,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('project_sprints', 'name')->ignore($this->projectSprintId())->where(
                    fn ($query) => $query
                        ->where('project_module_id', $this->projectModuleId())
                        ->whereNull('deleted_at')
                ),
            ],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function projectModuleId(): int|string|null
    {
        $projectModule = $this->input('project_module_id', $this->route('projectModule'));

        return is_object($projectModule) ? $projectModule->id : $projectModule;
    }

    public function projectSprintId(): int|string|null
    {
        $projectSprint = $this->route('projectSprint');

        return is_object($projectSprint) ? $projectSprint->id : $projectSprint;
    }
}

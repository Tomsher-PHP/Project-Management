<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = is_object($project) ? $project->id : $project;

        $projectModule = $this->route('projectModule');
        $projectModuleId = is_object($projectModule) ? $projectModule->id : $projectModule;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('project_modules', 'name')
                ->ignore($projectModuleId)
                ->where(fn ($query) => $query
                    ->where('project_id', $projectId)
                    ->whereNull('deleted_at'))],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:0'],
            'order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('project_modules', 'order')
                    ->ignore($projectModuleId)
                    ->where(fn ($query) => $query
                        ->where('project_id', $projectId)
                        ->whereNull('deleted_at')),
            ],
        ];
    }
}

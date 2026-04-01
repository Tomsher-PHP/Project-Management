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
        $projectModule = $this->route('projectModule');
        $projectModuleId = is_object($projectModule) ? $projectModule->id : $projectModule;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('project_modules', 'name')
                ->ignore($projectModuleId)
                ->where(fn ($query) => $query
                    ->where('project_id', $this->projectId())
                    ->whereNull('deleted_at'))],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function projectId(): int|string|null
    {
        $project = $this->route('project');

        return is_object($project) ? $project->id : $project;
    }
}

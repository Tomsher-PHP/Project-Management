<?php

namespace App\Http\Requests;

use App\Models\ProjectModule;
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
            'status_id' => ['nullable', 'integer', 'exists:agile_module_statuses,id'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'description' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'completed_at' => ['nullable', 'date'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function projectId(): int|string|null
    {
        $project = $this->route('project');

        return is_object($project) ? $project->id : $project;
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $projectModule = $this->route('projectModule');

                if (! $projectModule instanceof ProjectModule) {
                    return;
                }

                if (! $this->isProtectedProjectModule($projectModule)) {
                    return;
                }

                $incomingName = trim((string) $this->input('name', ''));
                $currentName = trim((string) $projectModule->name);

                if ($incomingName !== '' && $incomingName !== $currentName) {
                    $validator->errors()->add('name', $this->protectedModuleRenameMessage($projectModule));
                }
            },
        ];
    }

    private function isProtectedProjectModule(ProjectModule $projectModule): bool
    {
        return (bool) ($projectModule->is_backlog || $projectModule->is_system);
    }

    private function protectedModuleRenameMessage(ProjectModule $projectModule): string
    {
        return $projectModule->is_backlog
            ? 'The project backlog module name cannot be changed.'
            : 'System project modules cannot be renamed.';
    }
}

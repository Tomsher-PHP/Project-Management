<?php

namespace App\Http\Requests;

use App\Models\ProjectSprint;
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
            'project_milestone_id' => ['required', 'integer', 'exists:project_milestones,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('project_sprints', 'name')->ignore($this->projectSprintId())->where(
                    fn ($query) => $query
                        ->where('project_milestone_id', $this->projectMilestoneId())
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

    public function projectMilestoneId(): int|string|null
    {
        $projectMilestone = $this->input('project_milestone_id', $this->route('projectMilestone'));

        return is_object($projectMilestone) ? $projectMilestone->id : $projectMilestone;
    }

    public function projectSprintId(): int|string|null
    {
        $projectSprint = $this->route('projectSprint');

        return is_object($projectSprint) ? $projectSprint->id : $projectSprint;
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $projectSprint = $this->route('projectSprint');

                if (! $projectSprint instanceof ProjectSprint) {
                    return;
                }

                if (! $this->isProtectedProjectSprint($projectSprint)) {
                    return;
                }

                $incomingName = trim((string) $this->input('name', ''));
                $currentName = trim((string) $projectSprint->name);

                if ($incomingName !== '' && $incomingName !== $currentName) {
                    $validator->errors()->add('name', $this->protectedSprintRenameMessage($projectSprint));
                }
            },
        ];
    }

    private function isProtectedProjectSprint(ProjectSprint $projectSprint): bool
    {
        return (bool) ($projectSprint->is_backlog || $projectSprint->is_system);
    }

    private function protectedSprintRenameMessage(ProjectSprint $projectSprint): string
    {
        return $projectSprint->is_backlog
            ? 'The project backlog sprint name cannot be changed.'
            : 'System project sprints cannot be renamed.';
    }
}

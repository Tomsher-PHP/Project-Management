<?php

namespace App\Http\Requests;

use App\Models\ProjectMilestone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectMilestone = $this->route('projectMilestone');
        $projectMilestoneId = is_object($projectMilestone) ? $projectMilestone->id : $projectMilestone;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('project_milestones', 'name')
                ->ignore($projectMilestoneId)
                ->where(fn ($query) => $query
                    ->where('project_id', $this->projectId())
                    ->whereNull('deleted_at'))],
            'status_id' => ['nullable', 'integer', 'exists:agile_milestone_statuses,id'],
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
                $projectMilestone = $this->route('projectMilestone');

                if (! $projectMilestone instanceof ProjectMilestone) {
                    return;
                }

                if (! $this->isProtectedProjectMilestone($projectMilestone)) {
                    return;
                }

                $incomingName = trim((string) $this->input('name', ''));
                $currentName = trim((string) $projectMilestone->name);

                if ($incomingName !== '' && $incomingName !== $currentName) {
                    $validator->errors()->add('name', $this->protectedMilestoneRenameMessage($projectMilestone));
                }
            },
        ];
    }

    private function isProtectedProjectMilestone(ProjectMilestone $projectMilestone): bool
    {
        return (bool) ($projectMilestone->is_backlog || $projectMilestone->is_system);
    }

    private function protectedMilestoneRenameMessage(ProjectMilestone $projectMilestone): string
    {
        return $projectMilestone->is_backlog
            ? 'The project backlog milestone name cannot be changed.'
            : 'System project milestones cannot be renamed.';
    }
}

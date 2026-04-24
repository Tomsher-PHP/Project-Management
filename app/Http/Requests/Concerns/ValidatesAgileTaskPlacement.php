<?php

namespace App\Http\Requests\Concerns;

use App\Models\Project;
use App\Models\ProjectSprint;
use Illuminate\Validation\Validator;

trait ValidatesAgileTaskPlacement
{
    protected function validateAgileTaskPlacement(
        Validator $validator,
        ?Project $project,
        ?int $projectId,
        ?int $projectMilestoneId,
        ?int $projectSprintId
    ): void {
        if (! $project || ! $projectId || $project->project_flow === 'linear') {
            return;
        }

        if (! $projectSprintId) {
            return;
        }

        $selectedSprint = ProjectSprint::query()
            ->where('project_id', $projectId)
            ->find($projectSprintId);

        if ($selectedSprint && $projectMilestoneId && (int) $selectedSprint->project_milestone_id !== $projectMilestoneId) {
            $validator->errors()->add('project_sprint_id', 'Please choose a sprint from the selected milestone.');
        }
    }

    protected function nullableIntegerInput(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }
}

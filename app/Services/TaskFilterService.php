<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;

class TaskFilterService
{

    public function getFilters($user, $baseQuery)
    {
        $projectIds = (clone $baseQuery)->distinct()->pluck('project_id')->filter();

        $projectMilestoneIds = (clone $baseQuery)
            ->whereNotNull('project_milestone_id')
            ->distinct()
            ->pluck('project_milestone_id')
            ->filter();

        $projectSprintIds = (clone $baseQuery)
            ->whereNotNull('project_sprint_id')
            ->distinct()
            ->pluck('project_sprint_id')
            ->filter();

        return [
            'projects' => $this->getProjects($projectIds),
            'projectMilestones' => $this->getMilestones($projectMilestoneIds),
            'projectSprints' => $this->getSprints($projectSprintIds),
            'statuses' => $this->getStatuses(),
            'assignees' => $this->getAssignees(),
            'priorities' => $this->getPriorities(),
            'taskTypeOptions' => $this->getTaskTypes(),
            'taskModeOptions' => $this->getTaskModes(),
        ];
    }

    private function getProjects($ids)
    {
        return $ids->isEmpty()
            ? collect()
            : Project::whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function getMilestones($ids)
    {
        return $ids->isEmpty()
            ? collect()
            : ProjectMilestone::with('project:id,name')
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'project_id', 'name'])
            ->map(fn($m) => (object)[
                'id' => $m->id,
                'project_id' => $m->project_id,
                'name' => $m->project?->name
                    ? "{$m->project->name} / {$m->name}"
                    : $m->name,
            ]);
    }

    private function getSprints($ids)
    {
        return $ids->isEmpty()
            ? collect()
            : ProjectSprint::with(['project:id,name', 'projectMilestone:id,name'])
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'project_id', 'project_milestone_id', 'name'])
            ->map(fn($s) => (object)[
                'id' => $s->id,
                'project_id' => $s->project_id,
                'project_milestone_id' => $s->project_milestone_id,
                'name' => collect([
                    $s->project?->name,
                    $s->projectMilestone?->name,
                    $s->name,
                ])->filter()->implode(' / '),
            ]);
    }

    private function getStatuses()
    {
        return TaskStatus::active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'flow_type', 'color', 'is_default', 'is_completed']);
    }

    private function getAssignees()
    {
        return User::active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function getPriorities()
    {
        return config('project_constants.task_priorities', []);
    }

    private function getTaskTypes()
    {
        return TaskType::active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color']);
    }

    private function getTaskModes()
    {
        return TaskMode::active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color']);
    }

    // Apply filter clauses to a task query
    public function apply($query, array $filters)
    {
        return $query->filter($filters);
    }

    // Sort tasks based on requested criteria
    public function sort($query, array $filters)
    {
        return $query->sort($filters);
    }
}

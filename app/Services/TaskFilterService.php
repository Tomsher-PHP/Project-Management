<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectModule;
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

        $projectModuleIds = (clone $baseQuery)
            ->whereNotNull('project_module_id')
            ->distinct()
            ->pluck('project_module_id')
            ->filter();

        $projectSprintIds = (clone $baseQuery)
            ->whereNotNull('project_sprint_id')
            ->distinct()
            ->pluck('project_sprint_id')
            ->filter();

        return [
            'projects' => $this->getProjects($projectIds),
            'projectModules' => $this->getModules($projectModuleIds),
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

    private function getModules($ids)
    {
        return $ids->isEmpty()
            ? collect()
            : ProjectModule::with('project:id,name')
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
            : ProjectSprint::with(['project:id,name', 'projectModule:id,name'])
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'project_id', 'project_module_id', 'name'])
            ->map(fn($s) => (object)[
                'id' => $s->id,
                'project_id' => $s->project_id,
                'project_module_id' => $s->project_module_id,
                'name' => collect([
                    $s->project?->name,
                    $s->projectModule?->name,
                    $s->name,
                ])->filter()->implode(' / '),
            ]);
    }

    private function getStatuses()
    {
        return TaskStatus::active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'flow_type', 'color']);
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
        return $query
            ->when($filters['project_id'] ?? null, fn($q, $v) => $q->whereIn('project_id', (array) $v))
            ->when($filters['status_id'] ?? null, fn($q, $v) => $q->whereIn('status_id', (array) $v))
            ->when($filters['current_assignee_id'] ?? null, fn($q, $v) => $q->whereIn('current_assignee_id', (array) $v));
    }

    // Sort tasks based on requested criteria
    public function sort($query, array $filters)
    {
        return match ($filters['sort'] ?? null) {
            'latest' => $query->latest(),
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('title', 'asc'),
            'name_desc' => $query->orderBy('title', 'desc'),
            default => $query->orderBy('id', 'desc'),
        };
    }
}

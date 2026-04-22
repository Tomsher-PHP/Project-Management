<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Tag;
use App\Models\TaskMode;
use App\Models\TaskType;

class TaskFormService
{
    public function getCreateData($user)
    {
        return [
            'taskCreateProjects' => $this->getProjects($user),

            'taskTypeOptions' => $this->getTaskTypes(),
            'taskModeOptions' => $this->getTaskModes(),
            'nextTaskTypeSortOrder' => ((int) TaskType::max('sort_order')) + 1,
            'nextTaskModeSortOrder' => ((int) TaskMode::max('sort_order')) + 1,
            'tagOptions' => $this->getTags(),

            'taskPriorityOptions' => $this->getTaskPriorities(),
            'defaultTaskPriority' => $this->getDefaultPriority(),        ];
    }

    private function getProjects($user)
    {
        return Project::query()
            ->accessibleBy($user)
            ->with([
                'projectModules:id,project_id,name,is_backlog,is_system',
                'projectSprints:id,project_id,project_module_id,name,is_backlog,is_system',
                'activeMembers:id,name',
            ])
            ->orderBy('name')
            ->get(['id', 'project_code', 'name', 'project_flow', 'default_billable', 'default_task_estimate_seconds']);
    }

    private function getTaskTypes()
    {
        return TaskType::active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function getTaskModes()
    {
        return TaskMode::active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function getTags()
    {
        return Tag::active()->get();
    }

    private function getTaskPriorities()
    {
        return collect(config('project_constants.task_priorities', []))
            ->map(fn($config, $key) => (object)[
                'value' => $key,
                'label' => $config['label'] ?? ucfirst($key),
            ])
            ->values();
    }

    private function getDefaultPriority(): string
    {
        $priorities = config('project_constants.task_priorities', []);

        if (array_key_exists('medium', $priorities)) {
            return 'medium';
        }

        return (string) (array_key_first($priorities) ?? 'medium');
    }

    // public function getCreateData($user)
    // {
    //     return [
    //         'taskCreateProjects' => Project::query()
    //             ->accessibleBy($user)
    //             ->with([
    //                 'projectModules' => fn($q) => $q->select(...),
    //                 'projectSprints' => fn($q) => $q->select(...),
    //                 'activeMembers:id,name',
    //             ])
    //             ->orderBy('name')
    //             ->get(['id', 'project_code', 'name', 'project_flow', 'default_billable', 'default_task_estimate_seconds']),

    //         'taskTypeOptions' => TaskType::active()->get(),
    //         'taskModeOptions' => TaskMode::active()->get(),
    //         'tagOptions' => Tag::active()->get(),

    //         'defaultTaskPriority' => config('project_constants.default_priority'),
    //         'defaultTaskDueDate' => now()->addDay()->toDateString(),
    //     ];
    // }
}

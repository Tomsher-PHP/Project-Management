<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class DashboardServices
{
    /**
     * Get summary counts for dashboard
     *
     * @param User $user
     * @return array
     */
    public function getDashboardSummary(User $user): array
    {
        // Grouped Project Counts by type
        $projectCounts = Project::accessibleBy($user)
            ->join('project_statuses', 'projects.status_id', '=', 'project_statuses.id')
            ->whereNull('project_statuses.deleted_at')
            ->selectRaw('project_statuses.type, count(projects.id) as count')
            ->groupBy('project_statuses.type')
            ->pluck('count', 'type')
            ->toArray();

        $totalProjects = Project::accessibleBy($user)->count();

        // Grouped Task Counts by type
        $taskCounts = Task::accessibleBy($user)
            ->join('task_statuses', 'tasks.status_id', '=', 'task_statuses.id')
            ->whereNull('task_statuses.deleted_at')
            ->selectRaw('task_statuses.type, count(tasks.id) as count')
            ->groupBy('task_statuses.type')
            ->pluck('count', 'type')
            ->toArray();

        $totalTasks = Task::accessibleBy($user)->count();

        return [
            // Project counts
            'total_projects' => $totalProjects,
            'open_projects' => $projectCounts['open'] ?? 0,
            'in_progress_projects' => $projectCounts['in_progress'] ?? 0,
            'archived_projects' => $projectCounts['archived'] ?? 0,
            'completed_projects' => $projectCounts['completed'] ?? 0,

            // Task counts
            'total_tasks' => $totalTasks,
            'pending_tasks' => $taskCounts['pending'] ?? 0,
            'active_tasks' => $taskCounts['active'] ?? 0,
            'archived_tasks' => $taskCounts['archived'] ?? 0,
            'completed_tasks' => $taskCounts['completed'] ?? 0,
        ];
    }
}

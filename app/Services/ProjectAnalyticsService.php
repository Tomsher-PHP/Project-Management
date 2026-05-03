<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectAnalyticsService
{
    // Get task wise overview for a project, grouped by status
    public function getTaskStatusOverview(Project $project): Collection
    {
        // Base query
        $baseQuery = Task::query()
            ->where('project_id', $project->id)
            ->where('request_status', Task::REQUEST_APPROVED);

        // Get grouped counts
        $taskCounts = $baseQuery
            ->selectRaw('status_id, COUNT(*) as count')
            ->groupBy('status_id')
            ->pluck('count', 'status_id');

        // Remove null status (cleaner + avoids useless whereIn value)
        $taskCounts = $taskCounts->filter(fn($count, $statusId) => !is_null($statusId));

        // Fetch only used statuses
        return TaskStatus::query()
            ->withTrashed()
            ->forFlow($project->project_flow)
            ->when(
                $taskCounts->isNotEmpty(),
                fn($q) => $q->whereIn('id', $taskCounts->keys())
            )
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color'])
            ->map(fn(TaskStatus $status) => [
                'id' => $status->id,
                'name' => $status->name,
                'color' => $status->color ?: '#CBD5E1',
                'count' => (int) $taskCounts[$status->id],
            ])
            ->values();
    }

    // Get task assignment overview for a project, grouped by user
    public function getTaskAssigneeOverview(Project $project): Collection
    {
        $assignmentMetricsByUserId = TaskAssignmentLog::query()
            ->selectRaw('user_id, COUNT(DISTINCT task_id) as task_count, COALESCE(SUM(worked_time_seconds), 0) as worked_time_seconds')
            ->whereHas('task', function ($query) use ($project) {
                $query
                    ->where('project_id', $project->id)
                    ->where('request_status', Task::REQUEST_APPROVED);
            })
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        if ($assignmentMetricsByUserId->isEmpty()) {
            return collect();
        }

        return User::query()
            ->withTrashed()
            ->with('primaryAttachment')
            ->whereIn('id', $assignmentMetricsByUserId->keys())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (User $user) use ($assignmentMetricsByUserId) {
                $metrics = $assignmentMetricsByUserId->get($user->id);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'count' => (int) ($metrics?->task_count ?? 0),
                    'worked_time_seconds' => (int) ($metrics?->worked_time_seconds ?? 0),
                    'profile_image_url' => $user->profile_image_url,
                ];
            })
            ->sort(function (array $left, array $right) {
                $workedTimeComparison = $right['worked_time_seconds'] <=> $left['worked_time_seconds'];

                if ($workedTimeComparison !== 0) {
                    return $workedTimeComparison;
                }

                $taskCountComparison = $right['count'] <=> $left['count'];

                if ($taskCountComparison !== 0) {
                    return $taskCountComparison;
                }

                return strcmp($left['name'], $right['name']);
            })
            ->values();
    }
}

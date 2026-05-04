<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        if ($taskCounts->isEmpty()) {
            return collect();
        }

        // Fetch only used statuses
        return TaskStatus::query()
            ->withTrashed()
            ->forFlow($project->project_flow)
            ->whereIn('id', $taskCounts->keys())
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color'])
            ->map(fn(TaskStatus $status) => [
                'id' => $status->id,
                'name' => $status->name,
                'color' => $status->color ?: '#CBD5E1',
                'count' => (int) $taskCounts->get($status->id, 0),
            ])
            ->values();
    }

    // Get task assignment overview for a project, grouped by user
    public function getTaskAssigneeOverview(Project $project): Collection
    {
        $taskMetricsByUser = TaskAssignmentLog::query()
            ->join('tasks', 'tasks.id', '=', 'task_assignment_logs.task_id')
            ->selectRaw('
            task_assignment_logs.user_id,
            COALESCE(SUM(task_assignment_logs.worked_time_seconds), 0) as worked_time_seconds,
            COALESCE(MAX(tasks.estimated_time_seconds), 0) as estimated_time_seconds,
            task_assignment_logs.task_id
        ')
            ->where('tasks.project_id', $project->id)
            ->where('tasks.request_status', Task::REQUEST_APPROVED)
            ->whereNull('tasks.deleted_at')
            ->groupBy('task_assignment_logs.user_id', 'task_assignment_logs.task_id');

        $assignmentMetricsByUserId = DB::query()
            ->fromSub($taskMetricsByUser, 'task_metrics_by_user')
            ->selectRaw('
                user_id,
                COUNT(*) as task_count,
                COALESCE(SUM(worked_time_seconds), 0) as worked_time_seconds,
                COALESCE(SUM(estimated_time_seconds), 0) as estimated_time_seconds
            ')
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
                    'estimated_time_seconds' => (int) ($metrics?->estimated_time_seconds ?? 0),
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

    public function getMilestoneBurnupChartData(Project $project, ?int $interval = null): array
    {
        $endLabel = '__end__';
        $originLabel = '__origin__';
        $milestones = ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->where(function ($query) {
                $query
                    ->where('is_backlog', '!=', 1)
                    ->orWhereNull('is_backlog');
            })
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get([
                'id',
                'name',
                'sort_order',
                'estimated_time_seconds',
                'actual_time_seconds',
                'derived_time_seconds',
            ]);

        $estimatedTotalSeconds = 0;
        $actualTotalSeconds = 0;

        $estimatedData = [];
        $actualData = [];
        $hasMilestones = $milestones->isNotEmpty();

        if ($hasMilestones) {
            $estimatedData[] = [
                'x' => 0,
                'y' => $originLabel,
            ];

            $actualData[] = [
                'x' => 0,
                'y' => $originLabel,
            ];
        }

        foreach ($milestones as $milestone) {
            $estimatedSeconds = (int) ($milestone->estimated_time_seconds ?? 0);
            $actualSeconds = (int) ($milestone->actual_time_seconds ?? 0);

            $estimatedTotalSeconds += $estimatedSeconds;
            $actualTotalSeconds += $actualSeconds;

            $estimatedData[] = [
                'x' => round($estimatedTotalSeconds / 3600, 2),
                'y' => $milestone->name,
            ];

            $actualData[] = [
                'x' => round($actualTotalSeconds / 3600, 2),
                'y' => $milestone->name,
            ];
        }

        $labels = $milestones
            ->sortByDesc('sort_order')
            ->pluck('name')
            ->values()
            ->all();

        if ($hasMilestones) {
            array_unshift($labels, $endLabel);
            $labels[] = $originLabel;
        }

        $maxHours = max(
            collect($estimatedData)->max('x') ?? 0,
            collect($actualData)->max('x') ?? 0
        );

        $interval = $interval ?? $this->getChartHourInterval($maxHours);
        $interval = max(1, $interval);

        $roundedMaxHours = $maxHours > 0
            ? (int) (ceil($maxHours / $interval) * $interval)
            : $interval;

        return [
            'labels' => $labels,
            'end_label' => $hasMilestones ? $endLabel : null,
            'origin_label' => $hasMilestones ? $originLabel : null,
            'interval' => $interval,
            'max_hours' => $roundedMaxHours,
            'datasets' => [
                [
                    'label' => 'Estimated Hours',
                    'data' => $estimatedData,
                ],
                [
                    'label' => 'Actual Hours',
                    'data' => $actualData,
                ],
            ],
        ];
    }

    private function getChartHourInterval(float|int $totalHours): int
    {
        return match (true) {
            $totalHours <= 40 => 5,
            $totalHours <= 120 => 10,
            $totalHours <= 300 => 25,
            $totalHours <= 1000 => 50,
            $totalHours <= 1200 => 100,
            $totalHours <= 1500 => 150,
            default => 200,
        };
    }
}

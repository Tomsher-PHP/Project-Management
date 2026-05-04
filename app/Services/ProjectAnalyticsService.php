<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectAnalyticsService
{

    public function getProgressbar(Project $project): Collection
    {
        $estimatedSeconds = (int) ($project->estimated_time_seconds ?? 0);

        if ($estimatedSeconds <= 0) {
            return collect($this->emptyProgressbarPayload());
        }

        $workedSeconds = $this->getApprovedWorkedSeconds($project);
        $maxSeconds = max($estimatedSeconds, $workedSeconds, 1);
        $workedPercent = round(($workedSeconds / $maxSeconds) * 100, 1);
        $estimatedPercent = round(($estimatedSeconds / $maxSeconds) * 100, 1);

        $isExceeded = $workedSeconds > $estimatedSeconds;
        $isExact = $workedSeconds === $estimatedSeconds;
        $isEarly = $workedSeconds < $estimatedSeconds;
        $differenceSeconds = abs($estimatedSeconds - $workedSeconds);
        $differencePercentage = (int) round(($differenceSeconds / $estimatedSeconds) * 100);

        $statusLabel = match (true) {
            $isExceeded => 'Exceeded estimate',
            $isExact => 'Exactly on estimate',
            default => 'Within estimate',
        };

        $statusTextColor = match (true) {
            $isExceeded => 'text-red-600 dark:text-red-400',
            $isExact => 'text-blue-600 dark:text-blue-400',
            default => 'text-green-600 dark:text-green-400',
        };

        $workedBarColor = $isExceeded ? 'bg-red-500' : 'bg-green-500';
        $estimatedBarColor = 'bg-warning-300 dark:bg-bgray-600';

        return collect([
            'worked_seconds' => $workedSeconds,
            'estimated_seconds' => $estimatedSeconds,
            'worked_percent' => $workedPercent,
            'estimated_percent' => $estimatedPercent,
            'has_estimate' => true,
            'is_exceeded' => $isExceeded,
            'is_exact' => $isExact,
            'is_early' => $isEarly,
            'status_label' => $statusLabel,
            'status_text_color' => $statusTextColor,
            'worked_bar_color' => $workedBarColor,
            'estimated_bar_color' => $estimatedBarColor,
            'difference_seconds' => $differenceSeconds,
            'difference_percentage' => $differencePercentage,
        ]);
    }

    private function getApprovedWorkedSeconds(Project $project): int
    {
        return (int) TaskTimeLog::query()
            ->join('tasks', 'tasks.id', '=', 'task_time_logs.task_id')
            ->where('tasks.project_id', $project->id)
            ->where('tasks.request_status', Task::REQUEST_APPROVED)
            ->whereNull('tasks.deleted_at')
            ->where('task_time_logs.is_approved', true)
            ->sum('task_time_logs.duration_seconds');
    }

    private function emptyProgressbarPayload(): array
    {
        return [
            'worked_seconds' => 0,
            'estimated_seconds' => 0,
            'worked_percent' => 0,
            'estimated_percent' => 0,
            'has_estimate' => false,
            'is_exceeded' => false,
            'is_exact' => false,
            'is_early' => false,
            'status_label' => 'No estimate added',
            'status_text_color' => 'text-bgray-500 dark:text-bgray-300',
            'worked_bar_color' => 'bg-green-500',
            'estimated_bar_color' => 'bg-warning-300 dark:bg-bgray-600',
            'difference_seconds' => 0,
            'difference_percentage' => null,
        ];
    }

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
            'max_hours' => ($roundedMaxHours + $interval),
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

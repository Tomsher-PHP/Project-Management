<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use App\Models\ServiceType;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectAnalyticsService
{
    public function __construct(
        protected ?ProjectSummaryService $projectSummaryService = null
    ) {
        $this->projectSummaryService = $this->projectSummaryService ?? app(ProjectSummaryService::class);
    }

    public function getTimelineProgressbars(Project $project): Collection
    {
        $timelines = $project->projectTimelines()->orderBy('sort_order')->get();

        if ($timelines->isEmpty()) {
            $bar = $this->getProgressbar($project);
            $bar->put('timeline_name', 'Task Progress');
            $bar->put('is_active', true);
            return collect([$bar]);
        }

        return $timelines->map(function ($timeline) use ($project) {
            $bar = $this->getProgressbar($project, $timeline);
            $bar->put('timeline_name', $timeline->name);
            $bar->put('is_active', $timeline->id === $project->display_timeline?->id);
            return $bar;
        });
    }

    public function getProgressbar(Project $project, ?\App\Models\ProjectTimeline $timeline = null): Collection
    {
        $displayTimeline = $timeline ?? $project->display_timeline;
        $estimatedSeconds = (int) ($displayTimeline?->estimated_time_seconds ?? $project->estimated_time_seconds ?? 0);
        $customerEstimateSeconds = (int) ($displayTimeline?->customer_estimate_seconds ?? $project->customer_estimate_seconds ?? 0);

        if ($estimatedSeconds <= 0 && $customerEstimateSeconds <= 0) {
            return collect($this->emptyProgressbarPayload());
        }

        $workedSeconds = (int) ($displayTimeline?->actual_time_seconds ?? $this->getApprovedWorkedSeconds($project));
        $maxSeconds = max($estimatedSeconds, $customerEstimateSeconds, $workedSeconds, 1);
        $workedPercent = round(($workedSeconds / $maxSeconds) * 100, 1);
        $estimatedPercent = round(($estimatedSeconds / $maxSeconds) * 100, 1);
        $customerEstimatePercent = round(($customerEstimateSeconds / $maxSeconds) * 100, 1);

        $comparisonBaseSeconds = $estimatedSeconds > 0 ? $estimatedSeconds : $customerEstimateSeconds;

        $isExceeded = $workedSeconds > $comparisonBaseSeconds;
        $isExact = $workedSeconds === $comparisonBaseSeconds;
        $isEarly = $workedSeconds < $comparisonBaseSeconds;
        $differenceSeconds = abs($comparisonBaseSeconds - $workedSeconds);
        $differencePercentage = $comparisonBaseSeconds > 0 ? (int) round(($differenceSeconds / $comparisonBaseSeconds) * 100) : 0;

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

        $workedBarColor = $isExceeded ? 'bg-red-500' : 'bg-green-400';
        $estimatedBarColor = 'bg-warning-100 dark:bg-bgray-600';

        return collect([
            'worked_seconds' => $workedSeconds,
            'estimated_seconds' => $estimatedSeconds,
            'customer_estimate_seconds' => $customerEstimateSeconds,
            'worked_percent' => $workedPercent,
            'estimated_percent' => $estimatedPercent,
            'customer_estimate_percent' => $customerEstimatePercent,
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
            'customer_estimate_seconds' => 0,
            'worked_percent' => 0,
            'estimated_percent' => 0,
            'customer_estimate_percent' => 0,
            'has_estimate' => false,
            'is_exceeded' => false,
            'is_exact' => false,
            'is_early' => false,
            'status_label' => 'No estimate added',
            'status_text_color' => 'text-bgray-700 dark:text-bgray-300',
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
        $taskMetricsByUser = TaskTimeLog::query()
            ->join('tasks', 'tasks.id', '=', 'task_time_logs.task_id')
            ->selectRaw('
            task_time_logs.user_id,
            COALESCE(SUM(task_time_logs.duration_seconds), 0) as worked_time_seconds,
            COALESCE(MAX(tasks.estimated_time_seconds), 0) as estimated_time_seconds,
            task_time_logs.task_id
        ')
            ->where('tasks.project_id', $project->id)
            ->where('tasks.request_status', Task::REQUEST_APPROVED)
            ->whereNull('tasks.deleted_at')
            ->groupBy('task_time_logs.user_id', 'task_time_logs.task_id');

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

        $assigneeOverviewById = User::query()
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
            ->keyBy('id');

        $displayAssigneeOverview = $assigneeOverviewById
            ->filter(fn(array $assignee) => (int) ($assignee['worked_time_seconds'] ?? 0) > 0);

        $project
            ->membersAll()
            ->with('primaryAttachment')
            ->where('users.is_active', true)
            ->wherePivot('is_active', true)
            ->whereNull('project_members.removed_at')
            ->get(['users.id', 'users.name'])
            ->each(function (User $member) use ($assigneeOverviewById, $displayAssigneeOverview) {
                $metrics = $assigneeOverviewById->get($member->id, []);

                $displayAssigneeOverview->put($member->id, [
                    'id' => $member->id,
                    'name' => $metrics['name'] ?? $member->name,
                    'count' => (int) ($metrics['count'] ?? 0),
                    'worked_time_seconds' => (int) ($metrics['worked_time_seconds'] ?? 0),
                    'estimated_time_seconds' => (int) ($metrics['estimated_time_seconds'] ?? 0),
                    'profile_image_url' => $metrics['profile_image_url'] ?? $member->profile_image_url,
                ]);
            });

        return $displayAssigneeOverview
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

    /**
     * Get distinct project IDs that the given user is involved in.
     */
    public function getUserInvolvedProjectIds(User $user): array
    {
        // 1. Projects from active membership (removed_at is null)
        $memberProjectIds = ProjectMember::where('user_id', $user->id)
            ->whereNull('removed_at')
            ->pluck('project_id');

        // 2. Projects created by user
        $creatorProjectIds = Project::where('added_by', $user->id)
            ->pluck('id');

        // 3. Projects from task assignment logs
        $taskLogProjectIds = TaskAssignmentLog::where('user_id', $user->id)
            ->join('tasks', 'tasks.id', '=', 'task_assignment_logs.task_id')
            ->whereNull('tasks.deleted_at')
            ->pluck('tasks.project_id');

        // 4. Projects from current task assignees
        $currentTaskProjectIds = Task::where('current_assignee_id', $user->id)
            ->whereNull('deleted_at')
            ->pluck('project_id');

        return $memberProjectIds
            ->merge($creatorProjectIds)
            ->merge($taskLogProjectIds)
            ->merge($currentTaskProjectIds)
            ->unique()
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Base query for projects filtered by user access and optional project/user filters.
     */
    public function getFilteredProjectsQuery(User $authUser, ?User $selectedUser = null, ?int $projectId = null): Builder
    {
        $query = Project::query()
            ->accessibleBy($authUser)
            ->whereNull('projects.deleted_at');

        if ($projectId) {
            $query->where('projects.id', $projectId);
        }

        if ($selectedUser) {
            $query->whereIn('projects.id', $this->getUserInvolvedProjectIds($selectedUser));
        }

        return $query;
    }

    /**
     * 1. Projects by Service Type
     */
    public function getProjectsByServiceType(User $authUser, ?User $selectedUser = null, ?int $projectId = null): array
    {
        $baseProjects = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId);
        $projectIds = $baseProjects->pluck('projects.id')->toArray();

        if (empty($projectIds)) {
            return [
                'labels' => [],
                'values' => [],
                'colors' => [],
                'items' => [],
                'total_projects' => 0,
            ];
        }

        $serviceTypeCounts = DB::table('project_service_types')
            ->whereIn('project_id', $projectIds)
            ->selectRaw('service_type_id, COUNT(DISTINCT project_id) as count')
            ->groupBy('service_type_id')
            ->pluck('count', 'service_type_id');

        $serviceTypes = ServiceType::query()
            ->where(function ($q) use ($serviceTypeCounts) {
                $q->where('is_active', true)
                    ->orWhereIn('id', $serviceTypeCounts->keys());
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'color']);

        $assignedProjectIds = DB::table('project_service_types')
            ->whereIn('project_id', $projectIds)
            ->distinct()
            ->pluck('project_id')
            ->toArray();
        $unassignedCount = count(array_diff($projectIds, $assignedProjectIds));

        $labels = [];
        $values = [];
        $colors = [];
        $items = [];

        foreach ($serviceTypes as $serviceType) {
            $count = (int) ($serviceTypeCounts->get($serviceType->id) ?? 0);
            $color = $serviceType->color ?: '#3B82F6';

            $labels[] = $serviceType->name;
            $values[] = $count;
            $colors[] = $color;
            $items[] = [
                'id' => $serviceType->id,
                'name' => $serviceType->name,
                'code' => $serviceType->code,
                'count' => $count,
                'color' => $color,
            ];
        }

        if ($unassignedCount > 0) {
            $labels[] = 'Unassigned';
            $values[] = $unassignedCount;
            $colors[] = '#94A3B8';
            $items[] = [
                'id' => null,
                'name' => 'Unassigned',
                'code' => 'none',
                'count' => $unassignedCount,
                'color' => '#94A3B8',
            ];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
            'items' => $items,
            'total_projects' => count($projectIds),
        ];
    }

    /**
     * 2. Projects by Flow (Agile or Linear)
     */
    public function getProjectsByFlow(User $authUser, ?User $selectedUser = null, ?int $projectId = null): array
    {
        $baseProjects = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId);
        $flowCounts = $baseProjects
            ->selectRaw('project_flow, COUNT(*) as count')
            ->groupBy('project_flow')
            ->pluck('count', 'project_flow');

        $totalProjects = $flowCounts->sum();
        $agileCount = (int) ($flowCounts->get('agile') ?? 0);
        $linearCount = (int) ($flowCounts->get('linear') ?? 0);

        return [
            'labels' => ['Agile', 'Linear'],
            'values' => [$agileCount, $linearCount],
            'colors' => ['#3B82F6', '#10B981'],
            'items' => [
                [
                    'flow' => 'agile',
                    'name' => 'Agile',
                    'count' => $agileCount,
                    'color' => '#3B82F6',
                ],
                [
                    'flow' => 'linear',
                    'name' => 'Linear',
                    'count' => $linearCount,
                    'color' => '#10B981',
                ],
            ],
            'total_projects' => $totalProjects,
        ];
    }

    /**
     * 3. Projects by Status
     */
    public function getProjectsByStatus(User $authUser, ?User $selectedUser = null, ?int $projectId = null): array
    {
        $baseProjects = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId);
        $statusCounts = $baseProjects
            ->selectRaw('status_id, COUNT(*) as count')
            ->groupBy('status_id')
            ->pluck('count', 'status_id');

        $totalProjects = $statusCounts->sum();

        $statuses = ProjectStatus::query()
            ->where(function ($q) use ($statusCounts) {
                $q->where('is_active', true)
                    ->orWhereIn('id', $statusCounts->keys()->filter());
            })
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'color', 'type', 'is_completed']);

        $labels = [];
        $values = [];
        $colors = [];
        $items = [];

        foreach ($statuses as $status) {
            $count = (int) ($statusCounts->get($status->id) ?? 0);
            $color = $status->color ?: '#6B7280';

            $labels[] = $status->name;
            $values[] = $count;
            $colors[] = $color;
            $items[] = [
                'id' => $status->id,
                'name' => $status->name,
                'code' => $status->code,
                'count' => $count,
                'color' => $color,
                'type' => $status->type,
                'is_completed' => (bool) $status->is_completed,
            ];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
            'items' => $items,
            'total_projects' => $totalProjects,
        ];
    }

    /**
     * 4. Projects by Stage
     */
    public function getProjectsByStage(User $authUser, ?User $selectedUser = null, ?int $projectId = null): array
    {
        $baseProjects = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId);
        $stageCounts = $baseProjects
            ->selectRaw('project_stage_id, COUNT(*) as count')
            ->groupBy('project_stage_id')
            ->pluck('count', 'project_stage_id');

        $totalProjects = $stageCounts->sum();

        $stages = ProjectStage::query()
            ->where(function ($q) use ($stageCounts) {
                $q->where('is_active', true)
                    ->orWhereIn('id', $stageCounts->keys()->filter());
            })
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'color']);

        $labels = [];
        $values = [];
        $colors = [];
        $items = [];

        foreach ($stages as $stage) {
            $count = (int) ($stageCounts->get($stage->id) ?? 0);
            $color = $stage->color ?: '#64748B';

            $labels[] = $stage->name;
            $values[] = $count;
            $colors[] = $color;
            $items[] = [
                'id' => $stage->id,
                'name' => $stage->name,
                'code' => $stage->code,
                'count' => $count,
                'color' => $color,
            ];
        }

        $unassignedCount = (int) ($stageCounts->get(null) ?? 0);
        if ($unassignedCount > 0) {
            $labels[] = 'Unassigned';
            $values[] = $unassignedCount;
            $colors[] = '#94A3B8';
            $items[] = [
                'id' => null,
                'name' => 'Unassigned',
                'code' => 'none',
                'count' => $unassignedCount,
                'color' => '#94A3B8',
            ];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
            'items' => $items,
            'total_projects' => $totalProjects,
        ];
    }

    /**
     * 5. Upcoming Timeline Expirations
     */
    public function getUpcomingTimelineExpirations(User $authUser, ?User $selectedUser = null, ?int $projectId = null, int $limit = 50, ?string $endDate = null, int $days = 45): array
    {
        $baseProjects = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId);
        $projectIds = $baseProjects->pluck('projects.id')->toArray();

        if (empty($projectIds)) {
            return [
                'can_view' => true,
                'timelines' => [],
                'contracts' => [],
                'total_count' => 0,
            ];
        }

        $startDateStr = now()->toDateString();
        $maxEndDateStr = $endDate ? Carbon::parse($endDate)->toDateString() : now()->addDays($days)->toDateString();

        $timelines = ProjectTimeline::query()
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $startDateStr)
            ->whereDate('end_date', '<=', $maxEndDateStr)
            ->whereNotIn('status', [ProjectTimeline::STATUS_COMPLETED, ProjectTimeline::STATUS_CANCELLED])
            ->orderBy('end_date', 'asc')
            ->with([
                'project:id,name,project_code,customer_id,status_id,project_stage_id',
                'project.customer:id,name',
                'project.projectStatus:id,name,color',
                'project.projectStage:id,name,color',
                'project.serviceTypes:id,name,color',
            ])
            ->limit($limit)
            ->get();

        $today = now()->startOfDay();

        $items = $timelines->map(function (ProjectTimeline $timeline) use ($today) {
            $timelineEndDate = $timeline->end_date ? Carbon::parse($timeline->end_date)->startOfDay() : null;
            $daysRemaining = $timelineEndDate ? (int) $today->diffInDays($timelineEndDate, false) : 0;

            return [
                'id' => $timeline->id,
                'timeline_name' => $timeline->name,
                'timeline_type' => $timeline->type,
                'project_id' => $timeline->project_id,
                'project_name' => $timeline->project?->name ?? '--',
                'project_code' => $timeline->project?->project_code ?? '--',
                'customer_name' => $timeline->project?->customer?->name ?? '--',
                'service_types' => $timeline->project?->serviceTypes?->map(fn($st) => [
                    'id' => $st->id,
                    'name' => $st->name,
                    'color' => $st->color ?: '#3B82F6',
                ])->values()->all() ?? [],
                'service_type_names' => $timeline->project?->serviceTypes?->isNotEmpty()
                    ? $timeline->project->serviceTypes->pluck('name')->implode(', ')
                    : '--',
                'start_date' => $timeline->start_date?->format('Y-m-d'),
                'formatted_start_date' => $timeline->start_date?->format('d M Y') ?? '--',
                'end_date' => $timeline->end_date?->format('Y-m-d'),
                'formatted_end_date' => $timeline->end_date?->format('d M Y') ?? '--',
                'customer_end_date' => $timeline->customer_end_date?->format('Y-m-d'),
                'formatted_customer_end_date' => $timeline->customer_end_date?->format('d M Y') ?? '--',
                'days_remaining' => max(0, $daysRemaining),
                'is_expiring_soon' => $daysRemaining <= 14,
                'status' => $timeline->status,
                'status_label' => match ($timeline->status) {
                    ProjectTimeline::STATUS_ACTIVE => 'Active',
                    ProjectTimeline::STATUS_PLANNED => 'Planned',
                    default => 'Active',
                },
                'project_status' => [
                    'name' => $timeline->project?->projectStatus?->name ?? '--',
                    'color' => $timeline->project?->projectStatus?->color ?? '#6B7280',
                ],
                'project_stage' => [
                    'name' => $timeline->project?->projectStage?->name ?? '--',
                    'color' => $timeline->project?->projectStage?->color ?? '#64748B',
                ],
                'estimated_time_seconds' => (int) ($timeline->estimated_time_seconds ?? 0),
                'formatted_estimated_time' => $this->formatDuration((int) ($timeline->estimated_time_seconds ?? 0)),
                'customer_estimate_seconds' => (int) ($timeline->customer_estimate_seconds ?? 0),
                'formatted_customer_estimate' => $this->formatDuration((int) ($timeline->customer_estimate_seconds ?? 0)),
            ];
        })->values()->all();

        return [
            'can_view' => true,
            'timelines' => $items,
            'contracts' => $items,
            'total_count' => count($items),
        ];
    }

    /**
     * 6. Project Summary Counts
     */
    public function getProjectSummaryCounts(User $authUser, ?User $selectedUser = null, ?int $projectId = null): array
    {
        $baseProjects = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId);
        $projectIds = $baseProjects->pluck('projects.id')->toArray();

        $totalProjects = count($projectIds);

        if ($totalProjects === 0) {
            return [
                'total_projects' => 0,
                'open_projects' => 0,
                'in_progress_projects' => 0,
                'archived_projects' => 0,
                'completed_projects' => 0,
                'active_projects' => 0,
                'total_tasks' => 0,
                'pending_tasks' => 0,
                'active_tasks' => 0,
                'archived_tasks' => 0,
                'completed_tasks' => 0,
                'total_worked_seconds' => 0,
                'formatted_worked_time' => '00h 00m',
                'total_estimated_seconds' => 0,
                'formatted_estimated_time' => '00h 00m',
                'upcoming_timelines' => 0,
                'upcoming_contracts' => 0,
            ];
        }

        // Grouped Project Counts by status type (matching Dashboard)
        $projectCounts = Project::whereIn('projects.id', $projectIds)
            ->join('project_statuses', 'projects.status_id', '=', 'project_statuses.id')
            ->whereNull('project_statuses.deleted_at')
            ->selectRaw('project_statuses.type, count(projects.id) as count')
            ->groupBy('project_statuses.type')
            ->pluck('count', 'type')
            ->toArray();

        $openProjects = (int) ($projectCounts['open'] ?? 0);
        $inProgressProjects = (int) ($projectCounts['in_progress'] ?? 0);
        $archivedProjects = (int) ($projectCounts['archived'] ?? 0);
        $completedProjects = (int) ($projectCounts['completed'] ?? 0);

        // Tasks query
        $taskQuery = Task::accessibleBy($authUser)
            ->whereIn('tasks.project_id', $projectIds)
            ->where('tasks.request_status', '!=', 'rejected')
            ->whereNull('tasks.deleted_at');

        if ($selectedUser) {
            $taskQuery->where('tasks.current_assignee_id', $selectedUser->id);
        }

        // Grouped Task Counts by status type (matching Dashboard)
        $taskCounts = (clone $taskQuery)
            ->join('task_statuses', 'tasks.status_id', '=', 'task_statuses.id')
            ->whereNull('task_statuses.deleted_at')
            ->selectRaw('task_statuses.type, count(tasks.id) as count')
            ->groupBy('task_statuses.type')
            ->pluck('count', 'type')
            ->toArray();

        $totalTasks = (clone $taskQuery)->count();
        $pendingTasks = (int) ($taskCounts['pending'] ?? 0);
        $activeTasks = (int) ($taskCounts['active'] ?? 0);
        $archivedTasks = (int) ($taskCounts['archived'] ?? 0);
        $completedTasks = (int) ($taskCounts['completed'] ?? 0);

        // Worked seconds from approved task time logs
        $timeLogQuery = TaskTimeLog::query()
            ->join('tasks', 'tasks.id', '=', 'task_time_logs.task_id')
            ->whereIn('tasks.project_id', $projectIds)
            ->where('tasks.request_status', Task::REQUEST_APPROVED)
            ->whereNull('tasks.deleted_at')
            ->where('task_time_logs.is_approved', true);

        if ($selectedUser) {
            $timeLogQuery->where('task_time_logs.user_id', $selectedUser->id);
        }

        $totalWorkedSeconds = (int) $timeLogQuery->sum('task_time_logs.duration_seconds');

        // Total estimated seconds
        $estimatedTaskQuery = Task::query()
            ->whereIn('tasks.project_id', $projectIds)
            ->where('tasks.request_status', '!=', 'rejected')
            ->whereNull('tasks.deleted_at');

        if ($selectedUser) {
            $estimatedTaskQuery->where('tasks.current_assignee_id', $selectedUser->id);
        }

        $totalEstimatedSeconds = (int) $estimatedTaskQuery->sum('tasks.estimated_time_seconds');

        // Upcoming timelines count (checking end_date in next 45 days)
        $upcomingTimelinesCount = ProjectTimeline::query()
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->whereDate('end_date', '<=', now()->addDays(45)->toDateString())
            ->whereNotIn('status', [ProjectTimeline::STATUS_COMPLETED, ProjectTimeline::STATUS_CANCELLED])
            ->count();

        return [
            // Project counts (matching Dashboard)
            'total_projects' => $totalProjects,
            'open_projects' => $openProjects,
            'in_progress_projects' => $inProgressProjects,
            'archived_projects' => $archivedProjects,
            'completed_projects' => $completedProjects,
            'active_projects' => $openProjects + $inProgressProjects,

            // Task counts (matching Dashboard)
            'total_tasks' => $totalTasks,
            'pending_tasks' => $pendingTasks,
            'active_tasks' => $activeTasks,
            'archived_tasks' => $archivedTasks,
            'completed_tasks' => $completedTasks,

            // Extra metrics for compatibility
            'total_worked_seconds' => $totalWorkedSeconds,
            'formatted_worked_time' => $this->formatDuration($totalWorkedSeconds),
            'total_estimated_seconds' => $totalEstimatedSeconds,
            'formatted_estimated_time' => $this->formatDuration($totalEstimatedSeconds),
            'upcoming_timelines' => $upcomingTimelinesCount,
            'upcoming_contracts' => $upcomingTimelinesCount,
        ];
    }

    /**
     * Get details for a summary count tile (Projects / Tasks)
     */
    public function getTileDetails(User $authUser, string $type, ?User $selectedUser = null, ?int $projectId = null): array
    {
        $isProject = str_ends_with($type, '_projects');
        $isTask = str_ends_with($type, '_tasks');

        if (!$isProject && !$isTask) {
            abort(400, 'Invalid tile type');
        }

        $records = [];
        $viewAllUrl = '';
        $title = ucwords(str_replace('_', ' ', $type));

        if ($isProject) {
            $statusType = str_replace('_projects', '', $type); // 'total', 'open', 'in_progress', 'archived', 'completed'

            $query = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId)
                ->with(['customer.profileGrade', 'projectStatus']);

            $statusIds = [];
            if ($statusType !== 'total') {
                $statusIds = ProjectStatus::where('type', $statusType)->pluck('id')->toArray();
                $query->whereIn('projects.status_id', $statusIds);
            }

            $records = $query->latest('projects.created_at')->limit(15)->get();

            $params = [];
            if ($statusType !== 'total' && !empty($statusIds)) {
                $params['status_id'] = $statusIds;
            }
            if ($selectedUser) {
                $params['user_id'] = $selectedUser->id;
            }
            $viewAllUrl = route('projects.index', $params);
        } else {
            $statusType = str_replace('_tasks', '', $type); // 'total', 'pending', 'active', 'archived', 'completed'

            $projectIds = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId)
                ->pluck('projects.id')
                ->toArray();

            $query = Task::accessibleBy($authUser)
                ->whereIn('tasks.project_id', $projectIds)
                ->where('tasks.request_status', '!=', 'rejected')
                ->whereNull('tasks.deleted_at')
                ->with(['currentAssignee', 'status']);

            if ($selectedUser) {
                $query->where('tasks.current_assignee_id', $selectedUser->id);
            }

            $statusIds = [];
            if ($statusType !== 'total') {
                $statusIds = TaskStatus::where('type', $statusType)->pluck('id')->toArray();
                $query->whereIn('tasks.status_id', $statusIds);
            }

            $records = $query->latest('tasks.created_at')->limit(15)->get();

            $params = [];
            if ($statusType !== 'total' && !empty($statusIds)) {
                $params['status_id'] = $statusIds;
            }
            if ($projectId) {
                $params['project_id'] = $projectId;
            }
            if ($selectedUser) {
                $params['user_id'] = $selectedUser->id;
            }
            $viewAllUrl = route('tasks.index', $params);
        }

        return [
            'records' => $records,
            'title' => $title,
            'viewAllUrl' => $viewAllUrl,
            'isProject' => $isProject,
        ];
    }

    /**
     * 7a. Project-specific task analytics: Status chart
     */
    public function getProjectTaskStatusChart(User $authUser, ?User $selectedUser = null, ?int $projectId = null): array
    {
        $baseProjects = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId);
        $projectIds = $baseProjects->pluck('projects.id')->toArray();

        if (empty($projectIds)) {
            return [
                'labels' => [],
                'values' => [],
                'colors' => [],
                'items' => [],
                'total_tasks' => 0,
            ];
        }

        $taskQuery = Task::query()
            ->whereIn('tasks.project_id', $projectIds)
            ->where('tasks.request_status', '!=', 'rejected')
            ->whereNull('tasks.deleted_at');

        if ($selectedUser) {
            $taskQuery->where('tasks.current_assignee_id', $selectedUser->id);
        }

        $taskCounts = $taskQuery
            ->selectRaw('status_id, COUNT(DISTINCT tasks.id) as count')
            ->groupBy('status_id')
            ->pluck('count', 'status_id')
            ->filter(fn($count, $statusId) => ! is_null($statusId));

        if ($taskCounts->isEmpty()) {
            return [
                'labels' => [],
                'values' => [],
                'colors' => [],
                'items' => [],
                'total_tasks' => 0,
            ];
        }

        $statusesQuery = TaskStatus::query()
            ->withTrashed()
            ->whereIn('id', $taskCounts->keys());

        if ($projectId && ($project = Project::find($projectId))) {
            $statusesQuery->forFlow($project->project_flow)->orderBy('sort_order');
        } else {
            $statusesQuery->orderBy('sort_order')->orderBy('name');
        }

        $statuses = $statusesQuery->get(['id', 'name', 'color']);

        $labels = [];
        $values = [];
        $colors = [];
        $items = [];

        foreach ($statuses as $status) {
            $count = (int) $taskCounts->get($status->id, 0);
            $color = $status->color ?: '#CBD5E1';

            $labels[] = $status->name;
            $values[] = $count;
            $colors[] = $color;
            $items[] = [
                'id' => $status->id,
                'name' => $status->name,
                'count' => $count,
                'color' => $color,
            ];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
            'items' => $items,
            'total_tasks' => array_sum($values),
        ];
    }

    /**
     * 7b. Project-specific task analytics: Priority chart
     */
    public function getProjectTaskPriorityChart(User $authUser, ?User $selectedUser = null, ?int $projectId = null): array
    {
        $baseProjects = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId);
        $projectIds = $baseProjects->pluck('projects.id')->toArray();

        if (empty($projectIds)) {
            return [
                'labels' => [],
                'values' => [],
                'colors' => [],
                'items' => [],
                'total_tasks' => 0,
            ];
        }

        $priorities = config('project_constants.task_priorities', []);
        $classColorMap = [
            'bg-error-300' => '#DD3333',
            'bg-orange' => '#FF784B',
            'bg-primary' => '#3B82F6',
            'bg-success-400' => '#16A34A',
        ];

        $query = Task::query()
            ->join('task_statuses', 'tasks.status_id', '=', 'task_statuses.id')
            ->whereIn('tasks.project_id', $projectIds)
            ->where('tasks.request_status', '!=', 'rejected')
            ->where('task_statuses.is_completed', 0)
            ->whereNull('tasks.deleted_at')
            ->whereNull('task_statuses.deleted_at');

        if ($selectedUser) {
            $query->where('tasks.current_assignee_id', $selectedUser->id);
        }

        $taskCounts = $query
            ->selectRaw('tasks.priority, COUNT(DISTINCT tasks.id) as count')
            ->groupBy('tasks.priority')
            ->pluck('count', 'priority');

        $labels = [];
        $values = [];
        $colors = [];
        $items = [];

        foreach ($priorities as $key => $config) {
            $count = (int) ($taskCounts[$key] ?? 0);
            $bgClass = $config['bg_class'] ?? '';
            $color = $classColorMap[$bgClass] ?? ($config['color'] ?? '#CBD5E1');
            $label = $config['label'] ?? ucfirst($key);

            $labels[] = $label;
            $values[] = $count;
            $colors[] = $color;
            $items[] = [
                'priority' => $key,
                'label' => $label,
                'count' => $count,
                'color' => $color,
            ];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
            'items' => $items,
            'total_tasks' => array_sum($values),
        ];
    }

    /**
     * 8. User/project time comparison
     */
    public function getProjectTimeComparisonChart(User $authUser, ?User $selectedUser = null, ?Carbon $date = null, ?int $projectId = null): array
    {
        return $this->projectSummaryService->getTaskLoggedTimeChart($authUser, $selectedUser, $date, $projectId);
    }

    /**
     * Get users involved in projects that auth user is authorized to access,
     * following existing project membership and user authorization rules.
     */
    public function getProjectSelectableUsers(User $authUser): Collection
    {
        // 1. Get all project IDs accessible by the auth user
        $accessibleProjectIds = Project::query()
            ->accessibleBy($authUser)
            ->whereNull('projects.deleted_at')
            ->pluck('projects.id')
            ->toArray();

        if (empty($accessibleProjectIds)) {
            return collect();
        }

        // 2. Find all user IDs involved in these projects
        // 2a. Active project members
        $memberUserIds = DB::table('project_members')
            ->whereIn('project_id', $accessibleProjectIds)
            ->whereNull('removed_at')
            ->pluck('user_id');

        // 2b. Project creators
        $creatorUserIds = Project::query()
            ->whereIn('id', $accessibleProjectIds)
            ->whereNotNull('added_by')
            ->pluck('added_by');

        // 2c. Current task assignees
        $taskAssigneeUserIds = Task::query()
            ->whereIn('project_id', $accessibleProjectIds)
            ->whereNull('deleted_at')
            ->whereNotNull('current_assignee_id')
            ->pluck('current_assignee_id');

        // 2d. Users in task assignment logs
        $taskLogUserIds = DB::table('task_assignment_logs')
            ->join('tasks', 'tasks.id', '=', 'task_assignment_logs.task_id')
            ->whereIn('tasks.project_id', $accessibleProjectIds)
            ->whereNull('tasks.deleted_at')
            ->pluck('task_assignment_logs.user_id');

        $involvedUserIds = $memberUserIds
            ->merge($creatorUserIds)
            ->merge($taskAssigneeUserIds)
            ->merge($taskLogUserIds)
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        if (empty($involvedUserIds)) {
            return collect();
        }

        // 3. Filter by user authorization rules: accessibleBy($authUser) or authUser itself
        $usersQuery = User::query()
            ->whereIn('id', $involvedUserIds)
            ->whereNull('deleted_at');

        if (! ($authUser->is_super_admin || $authUser->can('user.view_all_users'))) {
            $usersQuery->where(function ($q) use ($authUser) {
                $q->accessibleBy($authUser)
                    ->orWhere('id', $authUser->id);
            });
        }

        return $usersQuery->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Search projects for Tom Select lazy loading, respecting auth and user-involvement scoping.
     */
    public function searchProjects(User $authUser, ?User $selectedUser = null, ?string $query = null, ?int $excludeProjectId = null, ?int $projectId = null, int $page = 1, int $limit = 50): Collection
    {
        $search = trim((string) $query);

        $projectsQuery = $this->getFilteredProjectsQuery($authUser, $selectedUser, $projectId);

        if ($excludeProjectId) {
            $projectsQuery->where('projects.id', '!=', $excludeProjectId);
        }

        if ($search !== '') {
            $projectsQuery->where(function ($sub) use ($search) {
                $sub->where('projects.name', 'like', "%{$search}%")
                    ->orWhere('projects.project_code', 'like', "%{$search}%");
            });
        }

        if ($page > 1) {
            $projectsQuery->offset(($page - 1) * $limit);
        }

        $projects = $projectsQuery
            ->orderBy('projects.name')
            ->limit($limit)
            ->get(['projects.id', 'projects.name', 'projects.project_code']);

        return $projects->map(fn(Project $p) => [
            'id' => $p->id,
            'value' => (string) $p->id,
            'name' => $p->name,
            'text' => $p->name,
            'subtype' => $p->project_code ?: '--',
            'project_code' => $p->project_code ?: '--',
        ]);
    }

    /**
     * Single project details and summary.
     */
    public function getSingleProjectDetails(User $authUser, Project $project, ?User $selectedUser = null): array
    {
        $project->loadMissing([
            'customer:id,name',
            'projectStatus:id,name,color,type',
            'projectStage:id,name,color',
            'serviceTypes:id,name,color',
            'projectTimelines',
        ]);

        $displayTimeline = $project->display_timeline;
        $timelineEndDate = $displayTimeline?->end_date ?? $project->end_date ?? $project->deadline;
        $customerEndDate = null;
        if ($authUser->can('project.customer_end_date') && $displayTimeline?->customer_end_date) {
            $customerEndDate = $displayTimeline->customer_end_date;
        }

        $summaryCounts = $this->getProjectSummaryCounts($authUser, $selectedUser, $project->id);

        return [
            'id' => $project->id,
            'name' => $project->name,
            'project_code' => $project->project_code ?: '--',
            'customer_name' => $project->customer?->name ?: '--',
            'project_flow' => $project->project_flow ?: 'linear',
            'project_flow_label' => ucfirst($project->project_flow ?: 'linear'),
            'status' => [
                'name' => $project->projectStatus?->name ?: '--',
                'color' => $project->projectStatus?->color ?: '#6B7280',
                'type' => $project->projectStatus?->type ?: 'open',
            ],
            'stage' => [
                'name' => $project->projectStage?->name ?: '--',
                'color' => $project->projectStage?->color ?: '#64748B',
            ],
            'service_types' => $project->serviceTypes->map(fn($st) => [
                'id' => $st->id,
                'name' => $st->name,
                'color' => $st->color ?: '#3B82F6',
            ])->values()->all(),
            'dates' => [
                'start_date' => $project->start_date?->format('Y-m-d'),
                'formatted_start_date' => $project->start_date?->format('d M Y') ?: '--',
                'end_date' => $timelineEndDate?->format('Y-m-d'),
                'formatted_end_date' => $timelineEndDate?->format('d M Y') ?: '--',
                'timeline_end_date' => $timelineEndDate?->format('Y-m-d'),
                'formatted_timeline_end_date' => $timelineEndDate?->format('d M Y') ?: '--',
                'contract_end_date' => $customerEndDate?->format('Y-m-d'),
                'formatted_contract_end_date' => $customerEndDate?->format('d M Y') ?: '--',
            ],
            'task_summary' => [
                'total_tasks' => $summaryCounts['total_tasks'],
                'active_tasks' => $summaryCounts['active_tasks'],
                'completed_tasks' => $summaryCounts['completed_tasks'],
                'pending_tasks' => $summaryCounts['pending_tasks'],
                'total_worked_seconds' => $summaryCounts['total_worked_seconds'],
                'formatted_worked_time' => $summaryCounts['formatted_worked_time'],
                'total_estimated_seconds' => $summaryCounts['total_estimated_seconds'],
                'formatted_estimated_time' => $summaryCounts['formatted_estimated_time'],
            ],
        ];
    }

    /**
     * Format duration seconds into '00h 00m' format.
     */
    public function formatDuration(int $seconds): string
    {
        $hours = intdiv(max(0, $seconds), 3600);
        $minutes = intdiv(max(0, $seconds) % 3600, 60);

        return sprintf('%02dh %02dm', $hours, $minutes);
    }
}

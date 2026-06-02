<?php

namespace App\Services\Reports;

use App\Exports\TimeTrackingReportExport;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\TaskTimeLog;
use App\Providers\AppServiceProvider;
use App\Services\UserTimelineService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class TimeTrackingReportService
{
    protected const EXPORTABLE_COLUMNS = [
        'project' => 'Project',
        'milestone' => 'Milestone',
        'sprint' => 'Sprint',
        'task' => 'Task',
        'user' => 'User',
        'date' => 'Date',
        'start_time' => 'Start Time',
        'end_time' => 'End Time',
        'duration' => 'Duration',
    ];

    public function __construct(
        protected UserTimelineService $userTimelineService
    ) {}

    public function getColumnLabels(): array
    {
        return self::EXPORTABLE_COLUMNS;
    }

    protected function baseQuery(Request $request, array $excludedFilters = []): Builder
    {
        $dateRange = $this->resolveStartedAtDateRange($request);
        $projectIds = in_array('project_id', $excludedFilters, true)
            ? []
            : $this->resolveFilterIds($request, ['project_id']);
        $taskIds = in_array('task_id', $excludedFilters, true)
            ? []
            : $this->resolveFilterIds($request, ['task_id']);
        $milestoneIds = $this->shouldExcludeAnyFilter(
            $excludedFilters,
            ['project_milestone_id', 'milestone_id']
        )
            ? []
            : $this->resolveFilterIds($request, ['project_milestone_id', 'milestone_id']);
        $sprintIds = $this->shouldExcludeAnyFilter(
            $excludedFilters,
            ['project_sprint_id', 'sprint_id']
        )
            ? []
            : $this->resolveFilterIds($request, ['project_sprint_id', 'sprint_id']);
        $userIds = in_array('user_id', $excludedFilters, true)
            || in_array('staff_id', $excludedFilters, true)
            ? []
            : $this->getSelectedUserIds($request);

        return TaskTimeLog::query()
            ->whereHas('task.project', function ($projectQuery) {
                $projectQuery->where('projects.is_system', false);
            })
            ->when($projectIds !== [], function ($q) use ($projectIds) {
                $q->whereHas('task', function ($taskQuery) use ($projectIds) {
                    $taskQuery->whereIn('project_id', $projectIds);
                });
            })
            ->when($userIds !== [], function ($q) use ($userIds) {
                $q->whereIn('user_id', $userIds);
            })
            ->when($taskIds !== [], function ($q) use ($taskIds) {
                $q->whereIn('task_id', $taskIds);
            })
            ->when($milestoneIds !== [], function ($q) use ($milestoneIds) {
                $q->whereHas('task', function ($taskQuery) use ($milestoneIds) {
                    $taskQuery->where(function ($milestoneQuery) use ($milestoneIds) {
                        $milestoneQuery
                            ->whereIn('project_milestone_id', $milestoneIds)
                            ->orWhereHas('projectSprint', function ($sprintQuery) use ($milestoneIds) {
                                $sprintQuery->whereIn('project_milestone_id', $milestoneIds);
                            });
                    });
                });
            })
            ->when($sprintIds !== [], function ($q) use ($sprintIds) {
                $q->whereHas('task', function ($taskQuery) use ($sprintIds) {
                    $taskQuery->whereIn('project_sprint_id', $sprintIds);
                });
            })
            ->when($dateRange['type'] === 'between', function ($q) use ($dateRange) {
                $q->whereBetween('started_at', [
                    $dateRange['start']->toDateString() . ' 00:00:00',
                    $dateRange['end']->toDateString() . ' 23:59:59',
                ]);
            })
            ->when($dateRange['type'] === 'single', function ($q) use ($dateRange) {
                $q->whereDate('started_at', $dateRange['date']->toDateString());
            });
    }

    protected function query(Request $request)
    {
        return $this->baseQuery($request)
            ->with([
                'user:id,name',
                'task:id,name,project_id,project_milestone_id,project_sprint_id',
                'task.project:id,name,project_flow',
                'task.projectMilestone:id,project_id,name',
                'task.projectSprint:id,project_id,project_milestone_id,name',
                'task.projectSprint.projectMilestone:id,project_id,name',
            ])
            ->orderByDesc('started_at')
            ->orderByDesc('id');
    }

    public function getReports(Request $request, int|string $perPage)
    {
        return $this->query($request)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getTotalMinutes(Request $request)
    {
        return round(
            $this->query($request)
                ->sum('duration_seconds') / 60
        );
    }

    public function getSelectedUserIds(Request $request): array
    {
        return $this->resolveFilterIds($request, ['user_id', 'staff_id']);
    }

    public function getFilterProjects(Request $request): Collection
    {
        return $this->baseQuery($request, ['project_id'])
            ->join('tasks', 'tasks.id', '=', 'task_time_logs.task_id')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->select('projects.id', 'projects.name')
            ->distinct()
            ->orderBy('projects.name')
            ->get();
    }

    public function getFilterUsers(Request $request): Collection
    {
        return $this->baseQuery($request, ['user_id', 'staff_id'])
            ->join('users', 'users.id', '=', 'task_time_logs.user_id')
            ->select('users.id', 'users.name')
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    public function getFilterMilestones(Request $request): Collection
    {
        $milestoneIds = $this->baseQuery($request, ['project_milestone_id', 'milestone_id'])
            ->join('tasks', 'tasks.id', '=', 'task_time_logs.task_id')
            ->leftJoin('project_sprints', 'project_sprints.id', '=', 'tasks.project_sprint_id')
            ->selectRaw('COALESCE(tasks.project_milestone_id, project_sprints.project_milestone_id) as milestone_id')
            ->pluck('milestone_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        return $milestoneIds->isEmpty()
            ? collect()
            : ProjectMilestone::with('project:id,name')
            ->whereIn('id', $milestoneIds)
            ->orderBy('name')
            ->get(['id', 'project_id', 'name'])
            ->map(fn($milestone) => (object) [
                'id' => $milestone->id,
                'project_id' => $milestone->project_id,
                'name' => $milestone->project?->name
                    ? "{$milestone->project->name} / {$milestone->name}"
                    : $milestone->name,
            ]);
    }

    public function getFilterSprints(Request $request): Collection
    {
        $sprintIds = $this->baseQuery($request, ['project_sprint_id', 'sprint_id'])
            ->join('tasks', 'tasks.id', '=', 'task_time_logs.task_id')
            ->whereNotNull('tasks.project_sprint_id')
            ->pluck('tasks.project_sprint_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        return $sprintIds->isEmpty()
            ? collect()
            : ProjectSprint::with(['project:id,name', 'projectMilestone:id,name'])
            ->whereIn('id', $sprintIds)
            ->orderBy('name')
            ->get(['id', 'project_id', 'project_milestone_id', 'name'])
            ->map(fn($sprint) => (object) [
                'id' => $sprint->id,
                'project_id' => $sprint->project_id,
                'project_milestone_id' => $sprint->project_milestone_id,
                'name' => collect([
                    $sprint->project?->name,
                    $sprint->projectMilestone?->name,
                    $sprint->name,
                ])->filter()->implode(' / '),
            ]);
    }

    public function shouldShowBreakRows(Request $request): bool
    {
        return count($this->getSelectedUserIds($request)) === 1;
    }

    public function buildDisplayRows(LengthAwarePaginator $reports, Request $request): Collection
    {
        $reportRows = collect($reports->items())->values();

        if ($reportRows->isEmpty()) {
            return collect();
        }

        $rows = $reportRows->map(fn(TaskTimeLog $report) => [
            'type' => 'report',
            'report' => $report,
        ]);

        if (! $this->shouldShowBreakRows($request)) {
            return $rows;
        }

        $selectedUserId = $this->getSelectedUserIds($request)[0];
        $breaksByReportId = $this->buildBreakRowsByReportId($reportRows, $selectedUserId);

        return $rows->flatMap(function (array $row) use ($breaksByReportId) {
            $reportId = (int) $row['report']->id;

            if (! isset($breaksByReportId[$reportId])) {
                return [$row];
            }

            return [
                $row,
                [
                    'type' => 'break',
                    'break' => $breaksByReportId[$reportId],
                ],
            ];
        })->values();
    }

    public function export(Request $request)
    {
        $columns = $this->resolveExportColumns($request);
        $formatedDate = AppServiceProvider::formatAppDate(now());

        return Excel::download(
            new TimeTrackingReportExport(
                $this->query($request)->get(),
                $columns
            ),
            'time-tracking-report-' . $formatedDate . '.xlsx'
        );
    }

    public function resolveExportColumns(Request $request): array
    {
        $allowedColumns = $this->getColumnLabels();
        $requestedColumns = $request->input('visible_columns', []);

        if (is_string($requestedColumns)) {
            $requestedColumns = array_filter(explode(',', $requestedColumns));
        }

        if (! is_array($requestedColumns)) {
            $requestedColumns = [];
        }

        $requestedLookup = collect($requestedColumns)
            ->map(fn($column) => (string) $column)
            ->filter()
            ->values()
            ->flip();

        $columns = collect($allowedColumns)
            ->filter(fn($_label, $key) => $requestedLookup->has($key))
            ->all();

        return $columns !== [] ? $columns : $allowedColumns;
    }

    protected function resolveFilterIds(Request $request, array $keys): array
    {
        return collect($keys)
            ->flatMap(function (string $key) use ($request) {
                $value = $request->input($key, []);

                if (! is_array($value)) {
                    $value = [$value];
                }

                return $value;
            })
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function shouldExcludeAnyFilter(array $excludedFilters, array $keys): bool
    {
        return collect($keys)->contains(
            fn(string $key) => in_array($key, $excludedFilters, true)
        );
    }

    protected function resolveStartedAtDateRange(Request $request): array
    {
        $startDate = $this->parseFilterDate($request->input('start_date'));
        $endDate = $this->parseFilterDate($request->input('end_date'));

        if ($startDate && $endDate) {
            if ($startDate->gt($endDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            return [
                'type' => 'between',
                'start' => $startDate,
                'end' => $endDate,
            ];
        }

        if ($startDate || $endDate) {
            return [
                'type' => 'single',
                'date' => $startDate ?? $endDate,
            ];
        }

        return ['type' => null];
    }

    protected function parseFilterDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', trim($value));
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === trim($value)
            ? $date
            : null;
    }

    protected function buildBreakRowsByReportId(Collection $reports, int $selectedUserId): array
    {
        $breakRowsByReportId = [];

        $reportsByDate = $reports
            ->filter(fn(TaskTimeLog $report) => $report->started_at !== null)
            ->groupBy(fn(TaskTimeLog $report) => $report->started_at
                ->copy()
                ->timezone($this->getAppTimezone())
                ->toDateString());

        foreach ($reportsByDate as $date => $dateReports) {
            $workedTaskSegments = $this->userTimelineService
                ->getWorkedTaskTimelineSegments($selectedUserId, $date);

            $mergedIntervals = $this->mergeTimelineIntervals($workedTaskSegments);

            if (count($mergedIntervals) < 2) {
                continue;
            }

            $assignedShift = $this->userTimelineService->getAssignedShift($selectedUserId, $date);
            $breakSegments = $this->userTimelineService->getBreakTimelineSegments(
                $workedTaskSegments,
                $assignedShift,
                $date
            );

            if ($breakSegments === []) {
                continue;
            }

            $visibleReportsByInterval = [];

            foreach ($dateReports->values() as $report) {
                $segment = $this->mapReportToTimelineSegment($report, $date);

                if (! $segment) {
                    continue;
                }

                $intervalIndex = $this->findMatchingIntervalIndex($segment, $mergedIntervals);

                if ($intervalIndex === null) {
                    continue;
                }

                $visibleReportsByInterval[$intervalIndex][] = $report;
            }

            for ($intervalIndex = count($mergedIntervals) - 1; $intervalIndex >= 1; $intervalIndex--) {
                if (
                    empty($visibleReportsByInterval[$intervalIndex]) ||
                    empty($visibleReportsByInterval[$intervalIndex - 1])
                ) {
                    continue;
                }

                $breakSegment = $this->findBreakSegmentBetweenIntervals(
                    $breakSegments,
                    $mergedIntervals[$intervalIndex - 1],
                    $mergedIntervals[$intervalIndex]
                );

                if (! $breakSegment) {
                    continue;
                }

                $lastVisibleReport = collect($visibleReportsByInterval[$intervalIndex])->last();

                if (! $lastVisibleReport) {
                    continue;
                }

                $breakRowsByReportId[(int) $lastVisibleReport->id] = $breakSegment;
            }
        }

        return $breakRowsByReportId;
    }

    protected function mapReportToTimelineSegment(TaskTimeLog $report, string $date): ?array
    {
        if (! $report->started_at) {
            return null;
        }

        $timezone = $this->getAppTimezone();
        $selectedDate = Carbon::parse($date, $timezone)->timezone($timezone);
        $dayStartLocal = $selectedDate->copy()->startOfDay();
        $dayEndExclusiveLocal = $dayStartLocal->copy()->addDay();
        $startedAtLocal = $report->started_at->copy()->timezone($timezone);
        $endedAtLocal = ($report->ended_at ?? now('UTC'))->copy()->timezone($timezone);
        $segmentStartLocal = $startedAtLocal->greaterThan($dayStartLocal)
            ? $startedAtLocal
            : $dayStartLocal->copy();
        $segmentEndLocal = $endedAtLocal->lessThan($dayEndExclusiveLocal)
            ? $endedAtLocal
            : $dayEndExclusiveLocal->copy();

        if (! $segmentEndLocal->greaterThan($segmentStartLocal)) {
            return null;
        }

        $startSeconds = $dayStartLocal->diffInSeconds($segmentStartLocal);
        $durationSeconds = $segmentStartLocal->diffInSeconds($segmentEndLocal);

        if ($durationSeconds <= 0) {
            return null;
        }

        return [
            'start_seconds' => $startSeconds,
            'end_seconds' => $startSeconds + $durationSeconds,
        ];
    }

    protected function mergeTimelineIntervals(array $segments): array
    {
        $intervals = collect($segments)
            ->map(function (array $segment) {
                $start = (int) ($segment['start_seconds'] ?? (($segment['start_minutes'] ?? 0) * 60));
                $end = (int) ($segment['end_seconds'] ?? (($segment['end_minutes'] ?? 0) * 60));

                return $end > $start
                    ? ['start_seconds' => $start, 'end_seconds' => $end]
                    : null;
            })
            ->filter()
            ->sortBy('start_seconds')
            ->values()
            ->all();

        $merged = [];

        foreach ($intervals as $interval) {
            if ($merged === []) {
                $merged[] = $interval;
                continue;
            }

            $lastIndex = count($merged) - 1;

            if ($interval['start_seconds'] <= $merged[$lastIndex]['end_seconds']) {
                $merged[$lastIndex]['end_seconds'] = max(
                    $merged[$lastIndex]['end_seconds'],
                    $interval['end_seconds']
                );
                continue;
            }

            $merged[] = $interval;
        }

        return $merged;
    }

    protected function findMatchingIntervalIndex(array $segment, array $intervals): ?int
    {
        foreach ($intervals as $index => $interval) {
            if (
                $segment['start_seconds'] >= $interval['start_seconds'] &&
                $segment['end_seconds'] <= $interval['end_seconds']
            ) {
                return $index;
            }
        }

        foreach ($intervals as $index => $interval) {
            $overlapStart = max($segment['start_seconds'], $interval['start_seconds']);
            $overlapEnd = min($segment['end_seconds'], $interval['end_seconds']);

            if ($overlapEnd > $overlapStart) {
                return $index;
            }
        }

        return null;
    }

    protected function findBreakSegmentBetweenIntervals(array $breakSegments, array $olderInterval, array $newerInterval): ?array
    {
        foreach ($breakSegments as $breakSegment) {
            if (
                (int) ($breakSegment['start_seconds'] ?? -1) === (int) $olderInterval['end_seconds'] &&
                (int) ($breakSegment['end_seconds'] ?? -1) === (int) $newerInterval['start_seconds']
            ) {
                return $breakSegment;
            }
        }

        return null;
    }

    protected function getAppTimezone(): string
    {
        return (string) config('constants.timezone', config('app.timezone'));
    }
}

<?php

namespace App\Services\Reports;

use App\Exports\ProductivityReportExport;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ProductivityReportService
{
    protected const DATE_RANGE_REQUEST_KEY = 'productivity_report.date_range';

    protected const EXPORTABLE_COLUMNS = [
        'user' => 'User',
        'tasks_count' => 'Tasks Count',
        'completed_tasks_count' => 'Completed Tasks Count',
        'estimated_hours' => 'Estimated Hours',
        'spend_hours' => 'Spend Hours',
        'efficiency' => 'Efficiency (%)',
    ];

    protected const SORTABLE_COLUMNS = [
        'user',
        'tasks_count',
        'completed_tasks_count',
        'estimated_hours',
        'spend_hours',
        'efficiency',
    ];

    public function getColumnLabels(): array
    {
        return self::EXPORTABLE_COLUMNS;
    }

    public function normalizeRequestFilters(Request $request): void
    {
        if (! $this->hasDateFilterSelection($request)) {
            return;
        }

        $dateRange = $this->resolveDateRange($request);
        $request->attributes->set(self::DATE_RANGE_REQUEST_KEY, $dateRange);

        $normalizedFilters = [];

        if ($request->filled('from_date') && $dateRange['start']) {
            $normalizedFilters['from_date'] = $dateRange['start']->toDateString();
        }

        if ($request->filled('to_date') && $dateRange['end']) {
            $normalizedFilters['to_date'] = $dateRange['end']->toDateString();
        }

        if ($normalizedFilters !== []) {
            $request->merge($normalizedFilters);
        }
    }

    public function getReportData(Request $request, int|string $perPage): array
    {
        $rows = $this->buildRows($request);
        $summaryStats = $this->buildSummaryStats($rows);

        return [
            'reports' => $this->paginateRows($rows, (int) $perPage, $request),
            'users' => $this->getFilterUsers($request),
            'columns' => $this->getColumnLabels(),
            'summaryStats' => $summaryStats,
            'canExport' => true,
        ];
    }

    public function export(Request $request)
    {
        $this->normalizeRequestFilters($request);

        $generatedAt = now((string) config('constants.timezone', config('app.timezone')));

        return Excel::download(
            new ProductivityReportExport(
                $this->buildRows($request),
                $this->resolveExportColumns($request),
                $request->all(),
                $generatedAt
            ),
            $this->buildExportFilename($generatedAt)
        );
    }

    public function getFilterUsers(Request $request): Collection
    {
        $userIds = $this->getAccessibleUserIds($request->user());

        if ($userIds === []) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function hasAppliedFilters(Request $request): bool
    {
        $dateRange = $this->resolveDateRange($request);

        return $dateRange['start'] !== null
            || $dateRange['end'] !== null
            || $this->resolveSelectedUserIds($request) !== [];
    }

    public function resolveExportColumns(Request $request): array
    {
        return $this->resolveExportColumnsFromFilters($request->all());
    }

    public function resolveExportColumnsFromFilters(array $filters): array
    {
        $allowedColumns = $this->getColumnLabels();
        $requestedColumns = $filters['visible_columns'] ?? [];

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

    protected function buildRows(Request $request): Collection
    {
        $timezone = (string) config('constants.timezone', 'UTC');
        $nowLocal = now($timezone);
        $dateRange = $this->resolveDateRange($request);
        $userIds = $this->getScopedUserIds($request);

        if ($userIds === [] || ! $dateRange['has_data']) {
            return collect();
        }

        $rangeStartLocal = $dateRange['start']?->copy()->startOfDay();
        $rangeEndExclusiveLocal = $dateRange['end']
            ? $dateRange['end']->copy()->addDay()->startOfDay()
            : $nowLocal->copy();
        $queryEndLocal = $dateRange['end'] && $dateRange['end']->isSameDay($nowLocal)
            ? $nowLocal->copy()
            : ($dateRange['end'] ? $rangeEndExclusiveLocal->copy() : $nowLocal->copy());

        $logsQuery = TaskTimeLog::query()
            ->whereIn('user_id', $userIds)
            ->where('started_at', '<=', $queryEndLocal->copy()->timezone('UTC'))
            ->where('is_running', false)
            ->with([
                'user:id,name',
                'task' => function ($query) {
                    $query
                        ->withTrashed()
                        ->select('id', 'name', 'status_id', 'task_mode_id', 'estimated_time_seconds', 'actual_time_seconds', 'break_work_request_id', 'request_type', 'request_status')
                        ->with([
                            'status' => fn($statusQuery) => $statusQuery
                                ->withTrashed()
                                ->select('id', 'name', 'type', 'is_completed'),
                            'taskMode' => fn($modeQuery) => $modeQuery
                                ->withTrashed()
                                ->select('id', 'name', 'is_productive', 'track_performance'),
                        ]);
                },
            ]);

        if ($rangeStartLocal) {
            $logsQuery->where(function ($query) use ($rangeStartLocal) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', $rangeStartLocal->copy()->timezone('UTC'));
            });
        }

        $aggregates = [];

        foreach ($logsQuery->get() as $log) {
            if (! $log->user || ! $log->task || ! $log->started_at) {
                continue;
            }

            if ($this->shouldSkipTask($log->task)) {
                continue;
            }

            $startedAtLocal = $log->started_at->copy()->timezone($timezone);
            $endedAtLocal = $log->ended_at?->copy()->timezone($timezone);

            if (! $endedAtLocal || ! $endedAtLocal->greaterThan($startedAtLocal)) {
                continue;
            }

            $cursor = $startedAtLocal->copy()->startOfDay();
            if ($rangeStartLocal && $cursor->lessThan($rangeStartLocal)) {
                $cursor = $rangeStartLocal->copy();
            }

            $effectiveEndLocal = $endedAtLocal->lessThan($nowLocal)
                ? $endedAtLocal
                : $nowLocal->copy();

            if ($dateRange['end']) {
                $rangeEndCapLocal = $dateRange['end']->isSameDay($nowLocal)
                    ? $nowLocal->copy()
                    : $rangeEndExclusiveLocal->copy();

                if ($effectiveEndLocal->greaterThan($rangeEndCapLocal)) {
                    $effectiveEndLocal = $rangeEndCapLocal;
                }
            }

            while ($cursor->lt($rangeEndExclusiveLocal) && $cursor->lt($effectiveEndLocal)) {
                $dayStartLocal = $cursor->copy();
                $dayEndLocal = $dayStartLocal->copy()->addDay();
                $dayEffectiveEndLocal = $dayStartLocal->isSameDay($nowLocal)
                    ? $nowLocal->copy()
                    : $dayEndLocal->copy();

                $segmentStart = $startedAtLocal->greaterThan($dayStartLocal)
                    ? $startedAtLocal
                    : $dayStartLocal->copy();

                $segmentEnd = $endedAtLocal->lessThan($dayEffectiveEndLocal)
                    ? $endedAtLocal
                    : $dayEffectiveEndLocal->copy();

                if ($segmentEnd->greaterThan($segmentStart)) {
                    $seconds = $segmentStart->diffInSeconds($segmentEnd);

                    if ($seconds > 0) {
                        $userId = (int) $log->user_id;

                        if (! isset($aggregates[$userId])) {
                            $aggregates[$userId] = [
                                'user_id' => $userId,
                                'user' => $log->user,
                                'user_name' => $log->user->name ?? 'Unknown',
                                'task_ids' => [],
                                'completed_task_ids' => [],
                                'estimated_task_seconds' => [],
                                'spend_seconds' => 0,
                                'productive_spend_seconds' => 0,
                            ];
                        }

                        $task = $log->task;
                        $taskId = (int) $task->id;

                        $aggregates[$userId]['task_ids'][$taskId] = true;

                        if ($task->status?->is_completed) {
                            $aggregates[$userId]['completed_task_ids'][$taskId] = true;
                        }

                        if (! array_key_exists($taskId, $aggregates[$userId]['estimated_task_seconds'])) {
                            $aggregates[$userId]['estimated_task_seconds'][$taskId] = max(0, (int) ($task->estimated_time_seconds ?? 0));
                        }

                        $aggregates[$userId]['spend_seconds'] += $seconds;

                        if ($task->taskMode?->is_productive ?? true) {
                            $aggregates[$userId]['productive_spend_seconds'] += $seconds;
                        }
                    }
                }

                $cursor->addDay();
            }
        }

        $rows = collect($aggregates)
            ->map(function (array $row) {
                $estimatedSeconds = (int) array_sum($row['estimated_task_seconds']);
                $spendSeconds = (int) $row['spend_seconds'];
                $efficiency = $this->calculateEfficiency($estimatedSeconds, $spendSeconds);

                return [
                    'user_id' => $row['user_id'],
                    'user' => $row['user'],
                    'user_name' => $row['user_name'],
                    'tasks_count' => count($row['task_ids']),
                    'completed_tasks_count' => count($row['completed_task_ids']),
                    'estimated_seconds' => $estimatedSeconds,
                    'estimated_hours' => formatSecondsToHMS($estimatedSeconds),
                    'spend_seconds' => $spendSeconds,
                    'spend_hours' => formatSecondsToHMS($spendSeconds),
                    'productive_spend_seconds' => (int) $row['productive_spend_seconds'],
                    'efficiency_percentage' => $efficiency,
                    'efficiency_label' => $this->formatPercentage($efficiency),
                    'efficiency_color_class' => $this->resolveEfficiencyColorClass($efficiency, $estimatedSeconds, $spendSeconds),
                ];
            })
            ->values();

        return $this->sortRows($rows, $request);
    }

    protected function shouldSkipTask($task): bool
    {
        return filled($task->break_work_request_id)
            || $task->request_type === 'break'
            || $task->request_status === 'rejected';
    }

    protected function calculateEfficiency(int $estimatedSeconds, int $spendSeconds): float
    {
        if ($estimatedSeconds <= 0 || $spendSeconds <= 0) {
            return 0.0;
        }

        return round(($spendSeconds / $estimatedSeconds) * 100, 2);
    }

    protected function resolveEfficiencyColorClass(float $efficiency, int $estimatedSeconds, int $spendSeconds): string
    {
        if ($estimatedSeconds <= 0 || $spendSeconds <= 0) {
            return 'text-bgray-500 dark:text-bgray-300 font-bold';
        }

        return $spendSeconds <= $estimatedSeconds
            ? 'text-success-400 dark:text-success-300 font-bold'
            : 'text-red-500 dark:text-red-400 font-bold';
    }

    protected function buildSummaryStats(Collection $rows): array
    {
        return [
            'total_result' => $rows->count(),
            'tasks' => (int) $rows->sum('tasks_count'),
            'completed' => (int) $rows->sum('completed_tasks_count'),
            'estimated_hours' => formatSecondsToHMS((int) $rows->sum('estimated_seconds')),
            'spend_hours' => formatSecondsToHMS((int) $rows->sum('spend_seconds')),
            'spend_hours_color_class' => $this->resolveEfficiencyColorClass(
                0,
                (int) $rows->sum('estimated_seconds'),
                (int) $rows->sum('spend_seconds')
            ),
        ];
    }

    protected function sortRows(Collection $rows, Request $request): Collection
    {
        $hasUserSelectedSort = $request->filled('sort_by');
        $sortBy = (string) $request->input('sort_by', 'efficiency');
        $sortDir = strtolower((string) $request->input('sort_dir', $hasUserSelectedSort ? 'asc' : 'desc')) === 'desc'
            ? 'desc'
            : 'asc';

        if (! in_array($sortBy, self::SORTABLE_COLUMNS, true)) {
            $sortBy = 'efficiency';
            $sortDir = 'desc';
        }

        $sorted = $rows->sort(function (array $left, array $right) use ($sortBy, $sortDir) {
            $leftValue = $this->resolveSortValue($left, $sortBy);
            $rightValue = $this->resolveSortValue($right, $sortBy);

            $comparison = is_string($leftValue) || is_string($rightValue)
                ? strcasecmp((string) $leftValue, (string) $rightValue)
                : ($leftValue <=> $rightValue);

            if ($comparison === 0) {
                $comparison = strcasecmp((string) $left['user_name'], (string) $right['user_name']);
            }

            return $sortDir === 'desc' ? -$comparison : $comparison;
        });

        return $sorted->values();
    }

    protected function resolveSortValue(array $row, string $sortBy): string|int|float
    {
        return match ($sortBy) {
            'user' => (string) ($row['user_name'] ?? ''),
            'tasks_count' => (int) ($row['tasks_count'] ?? 0),
            'completed_tasks_count' => (int) ($row['completed_tasks_count'] ?? 0),
            'estimated_hours' => (int) ($row['estimated_seconds'] ?? 0),
            'spend_hours' => (int) ($row['spend_seconds'] ?? 0),
            'efficiency' => (float) ($row['efficiency_percentage'] ?? 0),
            default => '',
        };
    }

    protected function formatPercentage(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%';
    }

    protected function paginateRows(Collection $rows, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            $rows->slice($offset, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    protected function getScopedUserIds(Request $request): array
    {
        $accessibleUserIds = $this->getAccessibleUserIds($request->user());
        $selectedUserIds = $this->resolveSelectedUserIds($request);

        if ($selectedUserIds === []) {
            return $accessibleUserIds;
        }

        return array_values(array_intersect($accessibleUserIds, $selectedUserIds));
    }

    protected function getAccessibleUserIds(User $user): array
    {
        return User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->push($user->id)
            ->map(fn($id) => (int) $id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function buildExportFilename(Carbon $generatedAt): string
    {
        return sprintf(
            'ProductivityReportExport_%s.xlsx',
            $generatedAt->format('Ymd_His')
        );
    }

    protected function resolveSelectedUserIds(Request $request): array
    {
        $value = $request->input('user_id', []);

        if (! is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->filter(fn($item) => filled($item))
            ->map(fn($item) => (int) $item)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveDateRange(Request $request): array
    {
        $cachedDateRange = $request->attributes->get(self::DATE_RANGE_REQUEST_KEY);

        if (is_array($cachedDateRange)) {
            return [
                'start' => $cachedDateRange['start']?->copy(),
                'end' => $cachedDateRange['end']?->copy(),
                'has_data' => (bool) $cachedDateRange['has_data'],
            ];
        }

        $timezone = (string) config('constants.timezone', 'UTC');
        $today = now($timezone)->startOfDay();

        $startDate = $this->parseDate($request->input('from_date'));
        $endDate = $this->parseDate($request->input('to_date'));

        if (! $startDate && ! $endDate) {
            return [
                'start' => null,
                'end' => null,
                'has_data' => true,
            ];
        }

        if ($startDate && $endDate && $startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if ($startDate && ! $endDate) {
            $endDate = $startDate->copy();
        }

        if (! $startDate && $endDate) {
            $startDate = null;
        }

        if ($endDate && $endDate->gt($today)) {
            $endDate = $today->copy();
        }

        return [
            'start' => $startDate?->copy(),
            'end' => $endDate?->copy(),
            'has_data' => ! $startDate || ! $endDate || $startDate->lte($endDate),
        ];
    }

    protected function hasDateFilterSelection(Request $request): bool
    {
        return $request->filled('from_date') || $request->filled('to_date');
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $rawValue = trim($value);
        $formats = collect([
            (string) config('constants.date_format', 'Y-m-d'),
            'Y-m-d',
            'd-M-Y',
        ])
            ->merge(config('constants.date_formats', []))
            ->filter(fn($format) => is_string($format) && $format !== '')
            ->unique()
            ->values();

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $rawValue);
            } catch (\Throwable) {
                continue;
            }

            if ($date && $date->format($format) === $rawValue) {
                return $date->startOfDay();
            }
        }

        return null;
    }
}

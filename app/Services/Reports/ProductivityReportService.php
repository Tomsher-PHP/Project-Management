<?php

namespace App\Services\Reports;

use App\Exports\ProductivityReportExport;
use App\Models\Project;
use App\Models\Task;
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
        'completed_tasks_count' => 'Completed Tasks',
        'estimated_hours' => 'Estimated Hours',
        'spend_hours' => 'Spend Hours',
        'saved_hours' => 'Saved',
        'efficiency' => 'Efficiency (%)',
    ];

    protected const SORTABLE_COLUMNS = [
        'user',
        'completed_tasks_count',
        'estimated_hours',
        'spend_hours',
        'saved_hours',
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
            'projects' => $this->getFilterProjects($request),
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

    public function getFilterProjects(Request $request): Collection
    {
        $projectIds = Task::query()
            ->accessibleBy($request->user())
            ->whereNull('tasks.deleted_at')
            ->whereNull('tasks.break_work_request_id')
            ->where(function ($query) {
                $query
                    ->whereNull('tasks.request_status')
                    ->orWhere('tasks.request_status', '!=', Task::REQUEST_REJECTED);
            })
            ->whereHas('status', function ($query) {
                $query->where('is_completed', true);
            })
            ->distinct()
            ->pluck('tasks.project_id')
            ->filter()
            ->values();

        if ($projectIds->isEmpty()) {
            return collect();
        }

        return Project::query()
            ->whereIn('id', $projectIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function hasAppliedFilters(Request $request): bool
    {
        $dateRange = $this->resolveDateRange($request);

        return $dateRange['start'] !== null
            || $dateRange['end'] !== null
            || $this->resolveSelectedProjectIds($request) !== []
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
        $dateRange = $this->resolveDateRange($request);
        $userIds = $this->getScopedUserIds($request);
        $projectIds = $this->resolveSelectedProjectIds($request);

        if ($userIds === [] || ! $dateRange['has_data']) {
            return collect();
        }

        $completedTasksQuery = Task::query()
            ->accessibleBy($request->user())
            ->whereNull('tasks.deleted_at')
            ->whereNull('tasks.break_work_request_id')
            ->where(function ($query) {
                $query
                    ->whereNull('tasks.request_status')
                    ->orWhere('tasks.request_status', '!=', Task::REQUEST_REJECTED);
            })
            ->whereHas('status', function ($query) {
                $query->where('is_completed', true);
            })
            ->when($projectIds !== [], function ($query) use ($projectIds) {
                $query->whereIn('tasks.project_id', $projectIds);
            })
            ->select('tasks.id', 'tasks.estimated_time_seconds', 'tasks.completed_at');

        if ($dateRange['start'] && $dateRange['end']) {
            $completedTasksQuery->whereBetween('tasks.completed_at', [
                $dateRange['start']->copy()->startOfDay(),
                $dateRange['end']->copy()->endOfDay(),
            ]);
        }

        $completedTasks = $completedTasksQuery->get();
        $taskEstimates = $completedTasks
            ->mapWithKeys(fn(Task $task) => [
                (int) $task->id => max(0, (int) ($task->estimated_time_seconds ?? 0)),
            ]);

        if ($taskEstimates->isEmpty()) {
            return collect();
        }

        $contributorLogs = TaskTimeLog::query()
            ->whereIn('task_id', $taskEstimates->keys())
            ->where('is_approved', true)
            ->where('is_running', false)
            ->selectRaw('task_id, user_id, COALESCE(SUM(duration_seconds), 0) as spend_seconds')
            ->groupBy('task_id', 'user_id')
            ->get();

        if ($contributorLogs->isEmpty()) {
            return collect();
        }

        $usersById = User::query()
            ->withTrashed()
            ->whereIn('id', $contributorLogs->pluck('user_id')->unique())
            ->select('id', 'name')
            ->get()
            ->keyBy('id');

        $aggregates = [];
        $scopedUserLookup = array_flip($userIds);

        $contributorLogs
            ->groupBy('task_id')
            ->each(function (Collection $taskLogs, int|string $taskId) use (&$aggregates, $taskEstimates, $usersById, $scopedUserLookup) {
                $taskId = (int) $taskId;
                $estimatedSeconds = (int) ($taskEstimates->get($taskId, 0));
                $totalSpendSeconds = (int) $taskLogs->sum(fn($log) => max(0, (int) ($log->spend_seconds ?? 0)));

                if ($totalSpendSeconds <= 0) {
                    return;
                }

                $estimatedShares = $this->allocateEstimateBySpend($estimatedSeconds, $taskLogs, $totalSpendSeconds);

                foreach ($taskLogs as $log) {
                    $userId = (int) $log->user_id;

                    if (! isset($scopedUserLookup[$userId])) {
                        continue;
                    }

                    $user = $usersById->get($userId);

                    if (! $user) {
                        continue;
                    }

                    if (! isset($aggregates[$userId])) {
                        $aggregates[$userId] = [
                            'user_id' => $userId,
                            'user' => $user,
                            'user_name' => $user->name ?? 'Unknown',
                            'completed_task_ids' => [],
                            'estimated_seconds' => 0,
                            'spend_seconds' => 0,
                        ];
                    }

                    $aggregates[$userId]['completed_task_ids'][$taskId] = true;
                    $aggregates[$userId]['estimated_seconds'] += (int) ($estimatedShares[$userId] ?? 0);
                    $aggregates[$userId]['spend_seconds'] += max(0, (int) ($log->spend_seconds ?? 0));
                }
            });

        $rows = collect($aggregates)
            ->map(function (array $row) {
                $estimatedSeconds = (int) $row['estimated_seconds'];
                $spendSeconds = (int) $row['spend_seconds'];
                $savedSeconds = $estimatedSeconds - $spendSeconds;
                $efficiency = $this->calculateEfficiency($estimatedSeconds, $spendSeconds);

                return [
                    'user_id' => $row['user_id'],
                    'user' => $row['user'],
                    'user_name' => $row['user_name'],
                    'completed_tasks_count' => count($row['completed_task_ids']),
                    'estimated_seconds' => $estimatedSeconds,
                    'estimated_hours' => formatSecondsToHMS($estimatedSeconds),
                    'spend_seconds' => $spendSeconds,
                    'spend_hours' => formatSecondsToHMS($spendSeconds),
                    'saved_seconds' => $savedSeconds,
                    'saved_hours' => $this->formatSignedSeconds($savedSeconds),
                    'efficiency_percentage' => $efficiency,
                    'efficiency_label' => $this->formatPercentage($efficiency),
                    'efficiency_color_class' => $this->resolveEfficiencyColorClass($efficiency, $estimatedSeconds, $spendSeconds),
                    'saved_color_class' => $this->resolveSavedColorClass($savedSeconds),
                    'spend_hours_color_class' => $this->resolveSavedColorClass($savedSeconds),
                ];
            })
            ->values();

        return $this->sortRows($rows, $request);
    }

    protected function allocateEstimateBySpend(int $estimatedSeconds, Collection $taskLogs, int $totalSpendSeconds): array
    {
        if ($estimatedSeconds <= 0 || $totalSpendSeconds <= 0) {
            return $taskLogs
                ->mapWithKeys(fn($log) => [(int) $log->user_id => 0])
                ->all();
        }

        $allocations = [];
        $remainders = [];
        $allocatedSeconds = 0;

        foreach ($taskLogs as $log) {
            $userId = (int) $log->user_id;
            $spendSeconds = max(0, (int) ($log->spend_seconds ?? 0));
            $rawShare = ($estimatedSeconds * $spendSeconds) / $totalSpendSeconds;
            $baseShare = (int) floor($rawShare);

            $allocations[$userId] = $baseShare;
            $allocatedSeconds += $baseShare;
            $remainders[] = [
                'user_id' => $userId,
                'remainder' => $rawShare - $baseShare,
                'spend_seconds' => $spendSeconds,
            ];
        }

        $remainingSeconds = $estimatedSeconds - $allocatedSeconds;

        if ($remainingSeconds <= 0) {
            return $allocations;
        }

        usort($remainders, function (array $left, array $right) {
            if ($left['remainder'] !== $right['remainder']) {
                return $right['remainder'] <=> $left['remainder'];
            }

            if ($left['spend_seconds'] !== $right['spend_seconds']) {
                return $right['spend_seconds'] <=> $left['spend_seconds'];
            }

            return $left['user_id'] <=> $right['user_id'];
        });

        foreach (array_slice($remainders, 0, $remainingSeconds) as $remainder) {
            $allocations[(int) $remainder['user_id']]++;
        }

        return $allocations;
    }

    protected function calculateEfficiency(int $estimatedSeconds, int $spendSeconds): float
    {
        if ($estimatedSeconds <= 0 || $spendSeconds <= 0) {
            return 0.0;
        }

        return round(($estimatedSeconds / $spendSeconds) * 100, 2);
    }

    protected function resolveEfficiencyColorClass(float $efficiency, int $estimatedSeconds, int $spendSeconds): string
    {
        if ($estimatedSeconds <= 0 || $spendSeconds <= 0) {
            return 'text-bgray-500 dark:text-bgray-300 font-bold';
        }

        return match (true) {
            $efficiency >= 120 => 'text-success-400 dark:text-success-300 font-bold',
            $efficiency >= 100 => 'text-success-300 dark:text-success-300 font-bold',
            $efficiency >= 80 => 'text-orange-500 dark:text-orange-300 font-bold',
            default => 'text-red-500 dark:text-red-400 font-bold',
        };
    }

    protected function resolveSavedColorClass(int $savedSeconds): string
    {
        if ($savedSeconds === 0) {
            return 'text-bgray-500 dark:text-bgray-300 font-bold';
        }

        return $savedSeconds > 0
            ? 'text-success-400 dark:text-success-300 font-bold'
            : 'text-red-500 dark:text-red-400 font-bold';
    }

    protected function buildSummaryStats(Collection $rows): array
    {
        $estimatedSeconds = (int) $rows->sum('estimated_seconds');
        $spendSeconds = (int) $rows->sum('spend_seconds');
        $savedSeconds = $estimatedSeconds - $spendSeconds;

        return [
            'total_result' => $rows->count(),
            'completed' => (int) $rows->sum('completed_tasks_count'),
            'estimated_hours' => formatSecondsToHMS($estimatedSeconds),
            'spend_hours' => formatSecondsToHMS($spendSeconds),
            'saved_hours' => $this->formatSignedSeconds($savedSeconds),
            'spend_hours_color_class' => $this->resolveSavedColorClass($savedSeconds),
            'saved_hours_color_class' => $this->resolveSavedColorClass($savedSeconds),
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
            'completed_tasks_count' => (int) ($row['completed_tasks_count'] ?? 0),
            'estimated_hours' => (int) ($row['estimated_seconds'] ?? 0),
            'spend_hours' => (int) ($row['spend_seconds'] ?? 0),
            'saved_hours' => (int) ($row['saved_seconds'] ?? 0),
            'efficiency' => (float) ($row['efficiency_percentage'] ?? 0),
            default => '',
        };
    }

    protected function formatPercentage(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%';
    }

    protected function formatSignedSeconds(int $seconds): string
    {
        if ($seconds === 0) {
            return formatSecondsToHMS(0);
        }

        return ($seconds > 0 ? '+' : '-') . formatSecondsToHMS(abs($seconds));
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

    protected function resolveSelectedProjectIds(Request $request): array
    {
        $value = $request->input('project_id', []);

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

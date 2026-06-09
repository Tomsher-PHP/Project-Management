<?php

namespace App\Services\Reports;

use App\Exports\DailyTimeReportExport;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Models\UserShiftAssignment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class DailyTimeReportService
{
    protected const DATE_RANGE_REQUEST_KEY = 'daily_time_report.date_range';

    protected const EXPORTABLE_COLUMNS = [
        'user' => 'User',
        'date' => 'Date',
        'shift_name' => 'Shift Name',
        'shift_time_from' => 'Shift Start',
        'shift_time_to' => 'Shift End',
        'start_time' => 'Start Time',
        'end_time' => 'End Time',
        'worked_time' => 'Worked Hours',
        'shift_hour' => 'Shift Hours',
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

        return [
            'reports' => $this->paginateRows($rows, (int) $perPage, $request),
            'users' => $this->getFilterUsers($request),
            'shifts' => $this->getFilterShifts($request),
            'columns' => $this->getColumnLabels(),
            'summaryStats' => [
                'total_users' => $rows->pluck('user_id')->unique()->count(),
                'total_records' => $rows->count(),
                'total_worked_time' => formatSecondsToHMS(
                    (int) $rows->sum('total_worked_seconds')
                ),
            ],
            'canExport' => $this->hasAppliedFilters($request),
        ];
    }

    public function export(Request $request)
    {
        $this->normalizeRequestFilters($request);

        if (! $this->hasAppliedFilters($request)) {
            throw ValidationException::withMessages([
                'export' => 'Apply at least one filter before exporting the Daily Time Report.',
            ]);
        }

        $generatedAt = now((string) config('constants.timezone', config('app.timezone')));

        return Excel::download(
            new DailyTimeReportExport(
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
            || $this->resolveSelectedUserIds($request) !== []
            || $this->resolveSelectedShiftIds($request) !== [];
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
        $dateFormat = (string) config('constants.date_format', 'Y-m-d');
        $timeFormat = (string) config('constants.time_format', 'H:i');
        $nowLocal = now($timezone);

        $dateRange = $this->resolveDateRange($request);
        $userIds = $this->getScopedUserIds($request);
        $selectedShiftIds = $this->resolveSelectedShiftIds($request);

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
            ->with([
                'user' => function ($query) {
                    $query->select('id', 'name')
                        ->with(['shiftAssignments.weekends', 'primaryAttachment']);
                },
            ]);

        if ($rangeStartLocal) {
            $logsQuery->where(function ($query) use ($rangeStartLocal) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', $rangeStartLocal->copy()->timezone('UTC'));
            });
        }

        $logs = $logsQuery->get();

        $aggregates = [];

        foreach ($logs as $log) {
            if (! $log->user || ! $log->started_at) {
                continue;
            }

            $startedAtLocal = $log->started_at->copy()->timezone($timezone);
            $endedAtLocal = $log->ended_at?->copy()->timezone($timezone);

            $cursor = $startedAtLocal->copy()->startOfDay();
            if ($rangeStartLocal && $cursor->lessThan($rangeStartLocal)) {
                $cursor = $rangeStartLocal->copy();
            }

            $effectiveEndLocal = $endedAtLocal
                ? ($endedAtLocal->lessThan($nowLocal) ? $endedAtLocal : $nowLocal->copy())
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

                $rawEnd = $endedAtLocal ?? $dayEffectiveEndLocal->copy();
                $segmentEnd = $rawEnd->lessThan($dayEffectiveEndLocal)
                    ? $rawEnd
                    : $dayEffectiveEndLocal->copy();

                if ($segmentEnd->greaterThan($segmentStart)) {
                    $rowKey = $log->user_id . '|' . $dayStartLocal->toDateString();

                    if (! isset($aggregates[$rowKey])) {
                        $aggregates[$rowKey] = [
                            'user_id' => $log->user_id,
                            'user' => $log->user,
                            'date' => $dayStartLocal->copy(),
                            'earliest_start' => null,
                            'latest_end' => null,
                            'latest_activity_at' => null,
                            'total_worked_seconds' => 0,
                            'has_running' => false,
                        ];
                    }

                    if (! $log->is_running) {
                        $seconds = $segmentStart->diffInSeconds($segmentEnd);
                        if ($seconds > 0) {
                            $aggregates[$rowKey]['total_worked_seconds'] += $seconds;
                        }
                    }

                    if (
                        ! $aggregates[$rowKey]['earliest_start'] ||
                        $segmentStart->lessThan($aggregates[$rowKey]['earliest_start'])
                    ) {
                        $aggregates[$rowKey]['earliest_start'] = $segmentStart->copy();
                    }

                    if (
                        ! $aggregates[$rowKey]['latest_activity_at'] ||
                        $segmentEnd->greaterThan($aggregates[$rowKey]['latest_activity_at'])
                    ) {
                        $aggregates[$rowKey]['latest_activity_at'] = $segmentEnd->copy();
                    }

                    if ($log->is_running) {
                        $aggregates[$rowKey]['has_running'] = true;
                    } elseif (
                        ! $aggregates[$rowKey]['latest_end'] ||
                        $segmentEnd->greaterThan($aggregates[$rowKey]['latest_end'])
                    ) {
                        $aggregates[$rowKey]['latest_end'] = $segmentEnd->copy();
                    }
                }

                $cursor->addDay();
            }
        }

        $rows = collect($aggregates)->map(function (array $row) use ($dateFormat, $timeFormat) {
            /** @var Carbon $selectedDate */
            $selectedDate = $row['date'];
            $user = $row['user'];
            $shiftDetails = $this->resolveShiftDetails(
                $user,
                $selectedDate,
                $row['earliest_start'],
                $row['latest_end'],
                $row['has_running'],
                $timeFormat
            );
            $totalWorkedSeconds = (int) $row['total_worked_seconds'];

            return [
                'user_id' => $row['user_id'],
                'user' => $user,
                'user_name' => $user->name ?? 'Unknown',
                'date' => $selectedDate->format($dateFormat),
                'shift_id' => $shiftDetails['shift_id'],
                'shift_name' => $shiftDetails['shift_name'],
                'shift_color_code' => $shiftDetails['shift_color_code'],
                'shift_time_from' => $shiftDetails['shift_time_from'],
                'shift_time_to' => $shiftDetails['shift_time_to'],
                'start_time' => $row['earliest_start']
                    ? $row['earliest_start']->format($timeFormat)
                    : '--',
                'start_time_status' => $shiftDetails['start_time_status'],
                'end_time' => $row['has_running']
                    ? 'Running'
                    : ($row['latest_end'] ? $row['latest_end']->format($timeFormat) : '--'),
                'end_time_status' => $shiftDetails['end_time_status'],
                'shift_working_hour' => $shiftDetails['shift_working_hour'],
                'shift_working_seconds' => $shiftDetails['shift_working_seconds'],
                'total_worked_time' => formatSecondsToHMS($totalWorkedSeconds),
                'total_worked_seconds' => $totalWorkedSeconds,
                'worked_time_status' => $shiftDetails['shift_working_seconds'] !== null
                    ? ($totalWorkedSeconds >= $shiftDetails['shift_working_seconds'] ? 'success' : 'danger')
                    : null,
                'sort_date' => $selectedDate->toDateString(),
                'latest_activity_timestamp' => $row['latest_activity_at']?->getTimestamp() ?? 0,
            ];
        })
            ->when($selectedShiftIds !== [], function (Collection $rows) use ($selectedShiftIds) {
                return $rows->filter(fn(array $row) => in_array((int) ($row['shift_id'] ?? 0), $selectedShiftIds, true));
            })
            ->values();

        return $rows->sort(function (array $left, array $right) {
            if ($left['sort_date'] !== $right['sort_date']) {
                return strcmp($right['sort_date'], $left['sort_date']);
            }

            if ($left['latest_activity_timestamp'] !== $right['latest_activity_timestamp']) {
                return $right['latest_activity_timestamp'] <=> $left['latest_activity_timestamp'];
            }

            return strcmp($left['user_name'], $right['user_name']);
        })->values()->map(function (array $row) {
            unset($row['sort_date'], $row['latest_activity_timestamp']);

            return $row;
        });
    }

    public function getFilterShifts(Request $request): Collection
    {
        $userIds = $this->getAccessibleUserIds($request->user());

        if ($userIds === []) {
            return collect();
        }

        return UserShiftAssignment::query()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('shift_id')
            ->select('shift_id', 'shift_name')
            ->get()
            ->map(fn(UserShiftAssignment $assignment) => (object) [
                'id' => (int) $assignment->shift_id,
                'name' => $assignment->shift_name,
            ])
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    protected function resolveShiftDetails(
        User $user,
        Carbon $selectedDate,
        ?Carbon $actualStart,
        ?Carbon $actualEnd,
        bool $hasRunning,
        string $timeFormat
    ): array
    {
        $assignment = $this->resolveShiftAssignmentForDate($user, $selectedDate);

        if (! $assignment) {
            return [
                'shift_id' => null,
                'shift_name' => '--',
                'shift_color_code' => null,
                'shift_time_from' => '--',
                'shift_time_to' => '--',
                'shift_working_hour' => '--',
                'shift_working_seconds' => null,
                'start_time_status' => null,
                'end_time_status' => null,
            ];
        }

        $weekNumber = (int) ceil($selectedDate->day / 7);
        $isWeekend = $assignment->weekends
            ->where('weekday', $selectedDate->dayOfWeek)
            ->where('week_number', $weekNumber)
            ->isNotEmpty();

        $shiftStart = $this->resolveShiftBoundary($selectedDate, $assignment->time_from);
        $shiftEnd = $this->resolveShiftBoundary($selectedDate, $assignment->time_to);

        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        if ($isWeekend) {
            $shiftWorkingHour = 'Day Off';
            $workingSeconds = null;
        } else {
            $workingSeconds = max(
                0,
                $shiftStart->diffInSeconds($shiftEnd) - (int) ($assignment->break_duration ?? 0)
            );

            $shiftWorkingHour = formatSecondsToHMS($workingSeconds);
        }

        return [
            'shift_id' => $assignment->shift_id ? (int) $assignment->shift_id : null,
            'shift_name' => $assignment->shift_name ?: '--',
            'shift_color_code' => $assignment->color_code ?: null,
            'shift_time_from' => $shiftStart->format($timeFormat),
            'shift_time_to' => $shiftEnd->format($timeFormat),
            'shift_working_hour' => $shiftWorkingHour,
            'shift_working_seconds' => $workingSeconds,
            'start_time_status' => $actualStart
                ? ($actualStart->lessThanOrEqualTo($shiftStart) ? 'success' : 'danger')
                : null,
            'end_time_status' => (! $hasRunning && $actualEnd)
                ? ($actualEnd->greaterThanOrEqualTo($shiftEnd) ? 'success' : 'danger')
                : null,
        ];
    }

    protected function resolveShiftBoundary(Carbon $selectedDate, mixed $time): Carbon
    {
        $timeValue = $time instanceof Carbon
            ? $time->format('H:i:s')
            : Carbon::parse($time)->format('H:i:s');

        return $selectedDate->copy()->setTimeFromTimeString($timeValue);
    }

    protected function resolveShiftAssignmentForDate(User $user, Carbon $selectedDate)
    {
        if (! $user->relationLoaded('shiftAssignments')) {
            return null;
        }

        return $user->shiftAssignments
            ->filter(function ($assignment) use ($selectedDate) {
                $dateFrom = $assignment->date_from
                    ? Carbon::parse($assignment->date_from)->startOfDay()
                    : null;
                $dateTo = $assignment->date_to
                    ? Carbon::parse($assignment->date_to)->endOfDay()
                    : null;

                if ($dateFrom && $selectedDate->lt($dateFrom)) {
                    return false;
                }

                if ($dateTo && $selectedDate->gt($dateTo)) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn($assignment) => $assignment->date_from ?? '')
            ->first();
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
            'daily_time_report_%s.xlsx',
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

    protected function resolveSelectedShiftIds(Request $request): array
    {
        $value = $request->input('shift_id', []);

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

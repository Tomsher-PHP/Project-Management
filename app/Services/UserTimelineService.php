<?php

namespace App\Services;

use App\Models\TaskTimeLog;
use App\Models\UserShiftAssignment;
use Illuminate\Support\Carbon;

class UserTimelineService
{
    public function getWorkedTaskTimelineSegments(int $userId, string|Carbon $date): array
    {
        [$dayStartLocal, $dayEndExclusiveLocal, $dayStartUtc, $dayEndExclusiveUtc] = $this->resolveLocalDayWindow($date);
        $nowUtc = Carbon::now('UTC');

        return TaskTimeLog::query()
            ->with('task:id,name')
            ->where('user_id', $userId)
            ->where('started_at', '<', $dayEndExclusiveUtc)
            ->where(function ($query) use ($dayStartUtc) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', $dayStartUtc);
            })
            ->orderBy('started_at')
            ->get()
            ->map(fn(TaskTimeLog $log) => $this->mapTaskTimeLogToTimelineSegment(
                $log,
                $dayStartLocal,
                $dayEndExclusiveLocal,
                $nowUtc
            ))
            ->filter()
            ->values()
            ->all();
    }

    public function getBreakTimelineSegments(array $workedTaskSegments, ?array $assignedShift, string|Carbon|null $date = null): array
    {
        [$selectedDate, $currentTimelineSecond, $isFutureDate] = $this->resolveTimelineProgressContext(
            $date ?? ($assignedShift['date'] ?? now())
        );

        if ($isFutureDate) {
            return [];
        }

        $workedIntervals = $this->mergeTimelineIntervals($workedTaskSegments);

        if (!empty($assignedShift['timeline_segments']) && ($assignedShift['is_working_day'] ?? false)) {
            $breakSegments = [];

            foreach ($assignedShift['timeline_segments'] as $shiftSegment) {
                $windowStart = (int) ($shiftSegment['start_seconds'] ?? 0);
                $windowEnd = (int) ($shiftSegment['end_seconds'] ?? 0);

                if ($currentTimelineSecond !== null) {
                    if ($windowStart >= $currentTimelineSecond) {
                        continue;
                    }

                    $windowEnd = min($windowEnd, $currentTimelineSecond);
                }

                if ($windowEnd <= $windowStart) {
                    continue;
                }

                $windowWorkIntervals = [];

                foreach ($workedIntervals as $interval) {
                    $overlapStart = max($windowStart, $interval['start_seconds']);
                    $overlapEnd = min($windowEnd, $interval['end_seconds']);

                    if ($overlapEnd > $overlapStart) {
                        $windowWorkIntervals[] = [
                            'start_seconds' => $overlapStart,
                            'end_seconds' => $overlapEnd,
                        ];
                    }
                }

                $windowWorkIntervals = $this->mergeTimelineIntervals($windowWorkIntervals);
                $cursor = $windowStart;

                foreach ($windowWorkIntervals as $interval) {
                    if ($interval['start_seconds'] > $cursor) {
                        $breakSegments[] = $this->formatBreakTimelineSegment($cursor, $interval['start_seconds']);
                    }

                    $cursor = max($cursor, $interval['end_seconds']);
                }

                if ($currentTimelineSecond === null && $cursor < $windowEnd) {
                    $breakSegments[] = $this->formatBreakTimelineSegment($cursor, $windowEnd);
                }
            }

            return array_values(array_filter($breakSegments));
        }

        $breakSegments = [];

        for ($index = 1; $index < count($workedIntervals); $index++) {
            $previousInterval = $workedIntervals[$index - 1];
            $currentInterval = $workedIntervals[$index];

            if ($currentInterval['start_seconds'] > $previousInterval['end_seconds']) {
                $breakSegments[] = $this->formatBreakTimelineSegment(
                    $previousInterval['end_seconds'],
                    $currentInterval['start_seconds']
                );
            }
        }

        return array_values(array_filter($breakSegments));
    }

    public function getTotalTimelineMinutes(array $segments): int
    {
        return (int) collect($segments)->sum(fn(array $segment) => (int) ($segment['duration_minutes'] ?? 0));
    }

    public function getTotalTimelineSeconds(array $segments): int
    {
        return (int) collect($segments)->sum(fn(array $segment) => (int) ($segment['duration_seconds'] ?? 0));
    }

    public function getAssignedShift(int $userId, string|Carbon $date): ?array
    {
        $date = $date instanceof Carbon
            ? $date->copy()->timezone($this->getAppTimezone())
            : Carbon::parse($date, $this->getAppTimezone())->timezone($this->getAppTimezone());

        $selectedDate = $date->copy()->startOfDay();
        $previousDate = $selectedDate->copy()->subDay();
        $selectedAssignment = $this->findAssignedShiftForDate($userId, $selectedDate);
        $previousAssignment = $this->findAssignedShiftForDate($userId, $previousDate);
        $timelineSegments = [];
        $assignment = $selectedAssignment ?? $previousAssignment;

        if ($previousAssignment && !$this->isWeekendForAssignment($previousAssignment, $previousDate)) {
            $timelineSegments = array_merge($timelineSegments, $this->buildShiftTimelineSegmentsForSelectedDate(
                $previousAssignment,
                $previousDate,
                $selectedDate
            ));
        }

        if ($selectedAssignment && !$this->isWeekendForAssignment($selectedAssignment, $selectedDate)) {
            $timelineSegments = array_merge($timelineSegments, $this->buildShiftTimelineSegmentsForSelectedDate(
                $selectedAssignment,
                $selectedDate,
                $selectedDate
            ));
        }

        if (!$assignment) {
            return null;
        }

        $isWeekend = $selectedAssignment
            ? $this->isWeekendForAssignment($selectedAssignment, $selectedDate)
            : true;
        $timeline = $timelineSegments[0] ?? null;

        return [
            'assignment_id' => $assignment->id,
            'user_id' => $assignment->user_id,

            'date' => $selectedDate->toDateString(),
            'weekday' => $selectedDate->dayOfWeek,
            'week_number' => $this->getWeekNumberOfMonth($selectedDate),

            'shift_id' => $assignment->shift_id,
            'shift_name' => $assignment->shift_name,
            'time_from' => $assignment->time_from,
            'time_to' => $assignment->time_to,
            'break_duration' => $assignment->break_duration,
            'color_code' => $assignment->color_code,

            'date_from' => $assignment->date_from,
            'date_to' => $assignment->date_to,

            'is_weekend' => $isWeekend,
            'is_working_day' => !empty($timelineSegments),
            'reason' => $assignment->reason,
            'timeline' => $timeline,
            'timeline_segments' => $timelineSegments,
        ];
    }

    /**
     * Return shift only if the date is a working day.
     */
    public function getWorkingShift(int $userId, string|Carbon $date): ?array
    {
        $shift = $this->getAssignedShift($userId, $date);

        if (!$shift || $shift['is_weekend']) {
            return null;
        }

        return $shift;
    }

    /**
     * Get week number inside the month.
     *
     * Example:
     * 1 to 7 = 1st week
     * 8 to 14 = 2nd week
     * 15 to 21 = 3rd week
     * 22 to 28 = 4th week
     * 29 to 31 = 5th week
     */
    private function getWeekNumberOfMonth(Carbon $date): int
    {
        return (int) ceil($date->day / 7);
    }

    private function findAssignedShiftForDate(int $userId, Carbon $date): ?UserShiftAssignment
    {
        return UserShiftAssignment::with('weekends')
            ->where('user_id', $userId)
            ->whereDate('date_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('date_to')
                    ->orWhereDate('date_to', '>=', $date->toDateString());
            })
            ->latest('date_from')
            ->latest('id')
            ->first();
    }

    private function isWeekendForAssignment(UserShiftAssignment $assignment, Carbon $date): bool
    {
        return $assignment->weekends
            ->where('weekday', $date->dayOfWeek)
            ->where('week_number', $this->getWeekNumberOfMonth($date))
            ->isNotEmpty();
    }

    private function resolveLocalDayWindow(string|Carbon $date): array
    {
        $timezone = $this->getAppTimezone();
        $selectedDate = $date instanceof Carbon
            ? $date->copy()->timezone($timezone)
            : Carbon::parse($date, $timezone)->timezone($timezone);

        $dayStartLocal = $selectedDate->copy()->startOfDay();
        $dayEndExclusiveLocal = $dayStartLocal->copy()->addDay();

        return [
            $dayStartLocal,
            $dayEndExclusiveLocal,
            $dayStartLocal->copy()->timezone('UTC'),
            $dayEndExclusiveLocal->copy()->timezone('UTC'),
        ];
    }

    private function resolveTimelineProgressContext(string|Carbon $date): array
    {
        $timezone = $this->getAppTimezone();
        $selectedDate = $date instanceof Carbon
            ? $date->copy()->timezone($timezone)->startOfDay()
            : Carbon::parse($date, $timezone)->timezone($timezone)->startOfDay();
        $today = Carbon::now($timezone)->startOfDay();

        if ($selectedDate->greaterThan($today)) {
            return [$selectedDate, null, true];
        }

        if ($selectedDate->lessThan($today)) {
            return [$selectedDate, null, false];
        }

        $now = Carbon::now($timezone);
        $currentTimelineSecond = ($now->hour * 3600) + ($now->minute * 60) + $now->second;

        return [$selectedDate, min($currentTimelineSecond, 86400), false];
    }

    private function buildShiftTimelineSegmentsForSelectedDate(UserShiftAssignment $assignment, Carbon $assignmentDate, Carbon $selectedDate): array
    {
        $startSeconds = $this->timeToSeconds($assignment->time_from);
        $endSeconds = $this->timeToSeconds($assignment->time_to);
        $breakDurationSeconds = max(0, (int) ($assignment->break_duration ?? 0));

        if ($startSeconds === null || $endSeconds === null) {
            return [];
        }

        $isOvernight = $endSeconds <= $startSeconds;
        $totalShiftDurationSeconds = $isOvernight
            ? (86400 - $startSeconds) + $endSeconds
            : ($endSeconds - $startSeconds);

        if (!$isOvernight) {
            if (!$assignmentDate->isSameDay($selectedDate)) {
                return [];
            }

            return [$this->formatShiftTimelineSegment(
                $assignment,
                $startSeconds,
                $endSeconds,
                $this->formatTimelineTime($assignment->time_from),
                $this->formatTimelineTime($assignment->time_to),
                $totalShiftDurationSeconds,
                $breakDurationSeconds,
                false
            )];
        }

        if ($assignmentDate->isSameDay($selectedDate)) {
            return [$this->formatShiftTimelineSegment(
                $assignment,
                $startSeconds,
                86400,
                $this->formatTimelineTime($assignment->time_from),
                null,
                $totalShiftDurationSeconds,
                $breakDurationSeconds,
                true
            )];
        }

        if ($assignmentDate->copy()->addDay()->isSameDay($selectedDate)) {
            return [$this->formatShiftTimelineSegment(
                $assignment,
                0,
                $endSeconds,
                null,
                $this->formatTimelineTime($assignment->time_to),
                $totalShiftDurationSeconds,
                $breakDurationSeconds,
                true
            )];
        }

        return [];
    }

    private function formatShiftTimelineSegment(UserShiftAssignment $assignment, int $startSeconds, int $endSeconds, ?string $startLabel, ?string $endLabel, int $totalShiftDurationSeconds, int $breakDurationSeconds, bool $isOvernightSegment): array
    {
        $durationSeconds = max(0, $endSeconds - $startSeconds);
        $actualStartLabel = $this->formatTimelineTime($assignment->time_from);
        $actualEndLabel = $this->formatTimelineTime($assignment->time_to);
        $actualBreakLabel = formatSecondsToHMS($breakDurationSeconds);
        $actualWorkingDurationSeconds = max(0, $totalShiftDurationSeconds - $breakDurationSeconds);
        $actualWorkingDurationLabel = formatSecondsToHMS($actualWorkingDurationSeconds);
        $tooltipLabel = trim(implode(' | ', array_filter([
            $assignment->shift_name,
            "{$actualStartLabel} - {$actualEndLabel}",
            "Work {$actualWorkingDurationLabel}",
            "Break {$actualBreakLabel}",
        ])));

        return [
            'left' => round(($startSeconds / 86400) * 100, 4),
            'width' => round(($durationSeconds / 86400) * 100, 4),
            'start_seconds' => $startSeconds,
            'end_seconds' => $endSeconds,
            'duration_seconds' => $durationSeconds,
            'start_minutes' => intdiv($startSeconds, 60),
            'end_minutes' => intdiv($endSeconds, 60),
            'duration_minutes' => intdiv($durationSeconds, 60),
            'start_label' => $startLabel,
            'end_label' => $endLabel,
            'duration_label' => $actualWorkingDurationLabel,
            'break_label' => $breakDurationSeconds > 0 ? $actualBreakLabel : null,
            'tooltip_label' => $tooltipLabel,
            'shift_name' => $assignment->shift_name,
            'actual_working_duration_seconds' => $actualWorkingDurationSeconds,
            'actual_break_duration_seconds' => $breakDurationSeconds,
            'actual_working_duration_minutes' => intdiv($actualWorkingDurationSeconds, 60),
            'actual_break_duration_minutes' => intdiv($breakDurationSeconds, 60),
            'color_code' => $assignment->color_code,
        ];
    }


    private function mapTaskTimeLogToTimelineSegment(TaskTimeLog $log, Carbon $dayStartLocal, Carbon $dayEndExclusiveLocal, Carbon $nowUtc): ?array
    {
        if (!$log->started_at) {
            return null;
        }

        $timezone = $this->getAppTimezone();
        $startedAtLocal = $log->started_at->copy()->timezone($timezone);
        $endedAtLocal = ($log->ended_at ?? $nowUtc)->copy()->timezone($timezone);
        $segmentStartLocal = $startedAtLocal->greaterThan($dayStartLocal)
            ? $startedAtLocal
            : $dayStartLocal->copy();
        $segmentEndLocal = $endedAtLocal->lessThan($dayEndExclusiveLocal)
            ? $endedAtLocal
            : $dayEndExclusiveLocal->copy();

        if (!$segmentEndLocal->greaterThan($segmentStartLocal)) {
            return null;
        }

        $startSecondsFromDayStart = $dayStartLocal->diffInSeconds($segmentStartLocal);
        $durationSeconds = $segmentStartLocal->diffInSeconds($segmentEndLocal);

        if ($durationSeconds <= 0) {
            return null;
        }

        return [
            'task_id' => $log->task_id,
            'task_name' => $log->task?->name ?? ('Task #' . $log->task_id),
            'left' => round(($startSecondsFromDayStart / 86400) * 100, 4),
            'width' => max(round(($durationSeconds / 86400) * 100, 4), 0.01),
            'start_seconds' => $startSecondsFromDayStart,
            'end_seconds' => $startSecondsFromDayStart + $durationSeconds,
            'duration_seconds' => $durationSeconds,
            'start_minutes' => intdiv($startSecondsFromDayStart, 60),
            'end_minutes' => intdiv($startSecondsFromDayStart + $durationSeconds, 60),
            'duration_minutes' => intdiv($durationSeconds, 60),
            'start_label' => $segmentStartLocal->format('H:i:s'),
            'end_label' => $segmentEndLocal->format('H:i:s'),
            'duration_label' => formatSecondsToHMS($durationSeconds),
        ];
    }

    private function mergeTimelineIntervals(array $segments): array
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
            if (empty($merged)) {
                $merged[] = $interval;
                continue;
            }

            $lastIndex = count($merged) - 1;

            if ($interval['start_seconds'] <= $merged[$lastIndex]['end_seconds']) {
                $merged[$lastIndex]['end_seconds'] = max($merged[$lastIndex]['end_seconds'], $interval['end_seconds']);
                continue;
            }

            $merged[] = $interval;
        }

        return $merged;
    }

    private function formatBreakTimelineSegment(int $startSeconds, int $endSeconds): ?array
    {
        $durationSeconds = $endSeconds - $startSeconds;

        if ($durationSeconds <= 0) {
            return null;
        }

        $startLabel = $this->formatSecondsAsTime($startSeconds);
        $endLabel = $this->formatSecondsAsTime($endSeconds);
        $durationLabel = formatSecondsToHMS($durationSeconds);

        return [
            'left' => round(($startSeconds / 86400) * 100, 4),
            'width' => max(round(($durationSeconds / 86400) * 100, 4), 0.01),
            'start_seconds' => $startSeconds,
            'end_seconds' => $endSeconds,
            'duration_seconds' => $durationSeconds,
            'start_minutes' => intdiv($startSeconds, 60),
            'end_minutes' => intdiv($endSeconds, 60),
            'duration_minutes' => intdiv($durationSeconds, 60),
            'start_label' => $startLabel,
            'end_label' => $endLabel,
            'duration_label' => $durationLabel,
            'tooltip_label' => "Break | {$startLabel} - {$endLabel} | {$durationLabel}",
        ];
    }

    private function timeToSeconds(mixed $time): ?int
    {
        if (!$time) {
            return null;
        }

        $timeString = $time instanceof Carbon
            ? $time->format('H:i:s')
            : (string) $time;

        [$hours, $minutes, $seconds] = array_pad(explode(':', $timeString), 3, '0');

        if ($hours === null || $minutes === null || $seconds === null) {
            return null;
        }

        return (((int) $hours) * 3600) + (((int) $minutes) * 60) + ((int) $seconds);
    }

    private function formatTimelineTime(mixed $time): ?string
    {
        if (!$time) {
            return null;
        }

        return $time instanceof Carbon
            ? $time->format('H:i')
            : substr(str_pad((string) $time, 8, ':00'), 0, 8);
    }

    private function formatSecondsAsTime(int $seconds): string
    {
        $normalizedSeconds = max(0, min($seconds, 86400));

        if ($normalizedSeconds === 86400) {
            return '00:00:00';
        }

        $hours = intdiv($normalizedSeconds, 3600);
        $remainingSeconds = $normalizedSeconds % 3600;
        $minutes = intdiv($remainingSeconds, 60);
        $secondsPart = $remainingSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secondsPart);
    }

    private function getAppTimezone(): string
    {
        return (string) config('constants.timezone', config('app.timezone'));
    }
}

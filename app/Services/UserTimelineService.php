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

    public function getBreakTimelineSegments(array $workedTaskSegments, ?array $assignedShift): array
    {
        $workedIntervals = $this->mergeTimelineIntervals($workedTaskSegments);

        if (!empty($assignedShift['timeline_segments']) && ($assignedShift['is_working_day'] ?? false)) {
            $breakSegments = [];

            foreach ($assignedShift['timeline_segments'] as $shiftSegment) {
                $windowStart = (int) ($shiftSegment['start_minutes'] ?? 0);
                $windowEnd = (int) ($shiftSegment['end_minutes'] ?? 0);

                if ($windowEnd <= $windowStart) {
                    continue;
                }

                $windowWorkIntervals = [];

                foreach ($workedIntervals as $interval) {
                    $overlapStart = max($windowStart, $interval['start_minutes']);
                    $overlapEnd = min($windowEnd, $interval['end_minutes']);

                    if ($overlapEnd > $overlapStart) {
                        $windowWorkIntervals[] = [
                            'start_minutes' => $overlapStart,
                            'end_minutes' => $overlapEnd,
                        ];
                    }
                }

                $windowWorkIntervals = $this->mergeTimelineIntervals($windowWorkIntervals);
                $cursor = $windowStart;

                foreach ($windowWorkIntervals as $interval) {
                    if ($interval['start_minutes'] > $cursor) {
                        $breakSegments[] = $this->formatBreakTimelineSegment($cursor, $interval['start_minutes']);
                    }

                    $cursor = max($cursor, $interval['end_minutes']);
                }

                if ($cursor < $windowEnd) {
                    $breakSegments[] = $this->formatBreakTimelineSegment($cursor, $windowEnd);
                }
            }

            return array_values(array_filter($breakSegments));
        }

        $breakSegments = [];

        for ($index = 1; $index < count($workedIntervals); $index++) {
            $previousInterval = $workedIntervals[$index - 1];
            $currentInterval = $workedIntervals[$index];

            if ($currentInterval['start_minutes'] > $previousInterval['end_minutes']) {
                $breakSegments[] = $this->formatBreakTimelineSegment(
                    $previousInterval['end_minutes'],
                    $currentInterval['start_minutes']
                );
            }
        }

        return array_values(array_filter($breakSegments));
    }

    public function getTotalTimelineMinutes(array $segments): int
    {
        return (int) collect($segments)->sum(fn(array $segment) => (int) ($segment['duration_minutes'] ?? 0));
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

    private function buildShiftTimelineSegmentsForSelectedDate(
        UserShiftAssignment $assignment,
        Carbon $assignmentDate,
        Carbon $selectedDate
    ): array {
        $startMinutes = $this->timeToMinutes($assignment->time_from);
        $endMinutes = $this->timeToMinutes($assignment->time_to);
        $breakDurationMinutes = max(0, (int) floor(((int) ($assignment->break_duration ?? 0)) / 60));

        if ($startMinutes === null || $endMinutes === null) {
            return [];
        }

        $isOvernight = $endMinutes <= $startMinutes;
        $totalShiftDurationMinutes = $isOvernight
            ? (1440 - $startMinutes) + $endMinutes
            : ($endMinutes - $startMinutes);

        if (!$isOvernight) {
            if (!$assignmentDate->isSameDay($selectedDate)) {
                return [];
            }

            return [$this->formatShiftTimelineSegment(
                $assignment,
                $startMinutes,
                $endMinutes,
                $this->formatTimelineTime($assignment->time_from),
                $this->formatTimelineTime($assignment->time_to),
                $totalShiftDurationMinutes,
                $breakDurationMinutes,
                false
            )];
        }

        if ($assignmentDate->isSameDay($selectedDate)) {
            return [$this->formatShiftTimelineSegment(
                $assignment,
                $startMinutes,
                1440,
                $this->formatTimelineTime($assignment->time_from),
                null,
                $totalShiftDurationMinutes,
                $breakDurationMinutes,
                true
            )];
        }

        if ($assignmentDate->copy()->addDay()->isSameDay($selectedDate)) {
            return [$this->formatShiftTimelineSegment(
                $assignment,
                0,
                $endMinutes,
                null,
                $this->formatTimelineTime($assignment->time_to),
                $totalShiftDurationMinutes,
                $breakDurationMinutes,
                true
            )];
        }

        return [];
    }

    private function formatShiftTimelineSegment(
        UserShiftAssignment $assignment,
        int $startMinutes,
        int $endMinutes,
        ?string $startLabel,
        ?string $endLabel,
        int $totalShiftDurationMinutes,
        int $breakDurationMinutes,
        bool $isOvernightSegment
    ): array {
        $durationMinutes = max(0, $endMinutes - $startMinutes);
        $actualStartLabel = $this->formatTimelineTime($assignment->time_from);
        $actualEndLabel = $this->formatTimelineTime($assignment->time_to);
        $actualBreakLabel = $this->formatDurationLabel($breakDurationMinutes);
        $actualWorkingDurationMinutes = max(0, $totalShiftDurationMinutes - $breakDurationMinutes);
        $actualWorkingDurationLabel = $this->formatDurationLabel($actualWorkingDurationMinutes);
        $tooltipLabel = trim(implode(' | ', array_filter([
            $assignment->shift_name,
            "{$actualStartLabel} - {$actualEndLabel}",
            "Work {$actualWorkingDurationLabel}",
            "Break {$actualBreakLabel}",
        ])));

        return [
            'left' => round(($startMinutes / 1440) * 100, 4),
            'width' => round(($durationMinutes / 1440) * 100, 4),
            'start_minutes' => $startMinutes,
            'end_minutes' => $endMinutes,
            'duration_minutes' => $durationMinutes,
            'start_label' => $startLabel,
            'end_label' => $endLabel,
            'duration_label' => $actualWorkingDurationLabel,
            'break_label' => $breakDurationMinutes > 0 ? $actualBreakLabel : null,
            'tooltip_label' => $tooltipLabel,
            'shift_name' => $assignment->shift_name,
            'color_code' => $assignment->color_code,
        ];
    }

    private function mapTaskTimeLogToTimelineSegment(
        TaskTimeLog $log,
        Carbon $dayStartLocal,
        Carbon $dayEndExclusiveLocal,
        Carbon $nowUtc
    ): ?array {
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

        $startMinutesFromDayStart = $dayStartLocal->diffInMinutes($segmentStartLocal);
        $durationMinutes = $segmentStartLocal->diffInMinutes($segmentEndLocal);

        if ($durationMinutes <= 0) {
            return null;
        }

        return [
            'task_id' => $log->task_id,
            'task_name' => $log->task?->name ?? ('Task #' . $log->task_id),
            'left' => round(($startMinutesFromDayStart / 1440) * 100, 4),
            'width' => max(round(($durationMinutes / 1440) * 100, 4), 0.01),
            'start_minutes' => $startMinutesFromDayStart,
            'end_minutes' => $startMinutesFromDayStart + $durationMinutes,
            'duration_minutes' => $durationMinutes,
            'start_label' => $segmentStartLocal->format('H:i'),
            'end_label' => $segmentEndLocal->format('H:i'),
            'duration_label' => $this->formatDurationLabel($durationMinutes),
        ];
    }

    private function mergeTimelineIntervals(array $segments): array
    {
        $intervals = collect($segments)
            ->map(function (array $segment) {
                $start = (int) ($segment['start_minutes'] ?? 0);
                $end = (int) ($segment['end_minutes'] ?? 0);

                return $end > $start
                    ? ['start_minutes' => $start, 'end_minutes' => $end]
                    : null;
            })
            ->filter()
            ->sortBy('start_minutes')
            ->values()
            ->all();

        $merged = [];

        foreach ($intervals as $interval) {
            if (empty($merged)) {
                $merged[] = $interval;
                continue;
            }

            $lastIndex = count($merged) - 1;

            if ($interval['start_minutes'] <= $merged[$lastIndex]['end_minutes']) {
                $merged[$lastIndex]['end_minutes'] = max($merged[$lastIndex]['end_minutes'], $interval['end_minutes']);
                continue;
            }

            $merged[] = $interval;
        }

        return $merged;
    }

    private function formatBreakTimelineSegment(int $startMinutes, int $endMinutes): ?array
    {
        $durationMinutes = $endMinutes - $startMinutes;

        if ($durationMinutes <= 0) {
            return null;
        }

        $startLabel = $this->formatMinutesAsTime($startMinutes);
        $endLabel = $this->formatMinutesAsTime($endMinutes);
        $durationLabel = $this->formatDurationLabel($durationMinutes);

        return [
            'left' => round(($startMinutes / 1440) * 100, 4),
            'width' => max(round(($durationMinutes / 1440) * 100, 4), 0.01),
            'start_minutes' => $startMinutes,
            'end_minutes' => $endMinutes,
            'duration_minutes' => $durationMinutes,
            'start_label' => $startLabel,
            'end_label' => $endLabel,
            'duration_label' => $durationLabel,
            'tooltip_label' => "Break | {$startLabel} - {$endLabel} | {$durationLabel}",
        ];
    }

    private function timeToMinutes(mixed $time): ?int
    {
        if (!$time) {
            return null;
        }

        $timeString = $time instanceof Carbon
            ? $time->format('H:i:s')
            : (string) $time;

        [$hours, $minutes] = array_pad(explode(':', $timeString), 2, null);

        if ($hours === null || $minutes === null) {
            return null;
        }

        return (((int) $hours) * 60) + ((int) $minutes);
    }

    private function formatTimelineTime(mixed $time): ?string
    {
        if (!$time) {
            return null;
        }

        return $time instanceof Carbon
            ? $time->format('H:i')
            : substr((string) $time, 0, 5);
    }

    private function formatMinutesAsTime(int $minutes): string
    {
        $normalizedMinutes = max(0, min($minutes, 1440));

        if ($normalizedMinutes === 1440) {
            return '00:00';
        }

        $hours = intdiv($normalizedMinutes, 60);
        $remainingMinutes = $normalizedMinutes % 60;

        return sprintf('%02d:%02d', $hours, $remainingMinutes);
    }

    private function formatDurationLabel(int $durationMinutes): string
    {
        $hours = intdiv($durationMinutes, 60);
        $minutes = $durationMinutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$minutes}m";
    }

    private function getAppTimezone(): string
    {
        return (string) config('constants.timezone', config('app.timezone'));
    }
}

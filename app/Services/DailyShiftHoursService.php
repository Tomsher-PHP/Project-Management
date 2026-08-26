<?php

namespace App\Services;

use App\Models\DailyShiftHourNotification;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Notifications\DailyShiftHoursShortNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DailyShiftHoursService
{
    protected UserTimelineService $userTimelineService;

    public function __construct(UserTimelineService $userTimelineService)
    {
        $this->userTimelineService = $userTimelineService;
    }

    /**
     * Main entry point to process daily shift hours evaluation for eligible employees.
     */
    public function processDailyShiftHoursCheck(): array
    {
        $timezone = (string) config('constants.timezone', config('app.timezone'));
        $gracePeriodMinutes = (int) config('constants.daily_work_notify', 60);

        $now = Carbon::now($timezone);

        // Candidate work dates to evaluate (today, yesterday, and 2 days ago)
        $candidateDates = [
            $now->toDateString(),
            $now->copy()->subDay()->toDateString(),
            $now->copy()->subDays(2)->toDateString(),
        ];

        $activeEmployees = User::query()
            ->active()
            ->where('delete_status', false)
            ->with(['details.reporter', 'details.manager'])
            ->get();

        $evaluatedCount = 0;
        $notificationsSentCount = 0;

        foreach ($activeEmployees as $employee) {
            foreach ($candidateDates as $workDateStr) {
                $evaluatedCount++;

                $notified = $this->evaluateUserShiftForDate(
                    $employee,
                    $workDateStr,
                    $now,
                    $gracePeriodMinutes,
                    $timezone
                );

                if ($notified) {
                    $notificationsSentCount++;
                }
            }
        }

        return [
            'checked_count' => $evaluatedCount,
            'sent_count' => $notificationsSentCount,
        ];
    }

    /**
     * Evaluate shift hours for a specific employee and work date.
     */
    protected function evaluateUserShiftForDate(
        User $employee,
        string $workDateStr,
        Carbon $now,
        int $gracePeriodMinutes,
        string $timezone
    ): bool {
        // 1. Check if notification has already been sent for this user & work date
        $alreadyNotified = DailyShiftHourNotification::query()
            ->where('user_id', $employee->id)
            ->where('work_date', $workDateStr)
            ->where('notification_type', 'short_hours')
            ->exists();

        if ($alreadyNotified) {
            return false;
        }

        // 2. Resolve assigned shift and working day status
        $assignedShift = $this->userTimelineService->getAssignedShift($employee->id, $workDateStr);

        if (
            !$assignedShift ||
            !empty($assignedShift['is_weekend']) ||
            empty($assignedShift['is_working_day'])
        ) {
            return false;
        }

        $timeFromStr = $this->extractTimeString($assignedShift['time_from'] ?? null);
        $timeToStr = $this->extractTimeString($assignedShift['time_to'] ?? null);

        if (!$timeFromStr || !$timeToStr) {
            return false;
        }

        // 3. Determine shift start & end timestamps (including overnight shift handling)
        $shiftStart = Carbon::parse($workDateStr . ' ' . $timeFromStr, $timezone);
        $timeFromSeconds = strtotime($timeFromStr);
        $timeToSeconds = strtotime($timeToStr);
        $isOvernight = $timeToSeconds <= $timeFromSeconds;

        if ($isOvernight) {
            $shiftEnd = Carbon::parse($workDateStr . ' ' . $timeToStr, $timezone)->addDay();
        } else {
            $shiftEnd = Carbon::parse($workDateStr . ' ' . $timeToStr, $timezone);
        }

        // 4. Check evaluation timing: shift_end + grace_period must be reached
        $evaluationTime = $shiftEnd->copy()->addMinutes($gracePeriodMinutes);

        if ($now->lessThan($evaluationTime)) {
            return false;
        }

        // 5. Verify employee is not currently actively working
        $isActivelyWorking = TaskTimeLog::query()
            ->where('user_id', $employee->id)
            ->where('is_running', true)
            ->exists();

        if ($isActivelyWorking) {
            return false;
        }

        // 6. Calculate required, worked, and short seconds
        $workedTaskSegments = $this->userTimelineService->getWorkedTaskTimelineSegments($employee->id, $workDateStr);
        $workedSeconds = $this->userTimelineService->getTotalTimelineSeconds($workedTaskSegments);

        $workedDiffData = $this->userTimelineService->getWorkedShiftDiff($assignedShift, $workedSeconds);
        $requiredSeconds = (int) ($workedDiffData['target_shift_seconds'] ?? 0);

        if ($requiredSeconds <= 0) {
            return false;
        }

        $shortSeconds = $requiredSeconds - $workedSeconds;

        if ($shortSeconds <= 0) {
            return false;
        }

        // 7. Record notification state atomically to prevent duplicates
        try {
            DailyShiftHourNotification::create([
                'user_id' => $employee->id,
                'user_shift_assignment_id' => $assignedShift['assignment_id'] ?? null,
                'work_date' => $workDateStr,
                'notification_type' => 'short_hours',
                'required_seconds' => $requiredSeconds,
                'worked_seconds' => $workedSeconds,
                'short_seconds' => $shortSeconds,
                'sent_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Already created by another process
            return false;
        }

        // 8. Resolve unique recipients and queue notification
        $recipients = $this->resolveNotificationRecipients($employee);

        $timeFromFormatted = Carbon::parse($timeFromStr)->format('H:i');
        $timeToFormatted = Carbon::parse($timeToStr)->format('H:i');
        $shiftTimeRange = "{$timeFromFormatted} - {$timeToFormatted}";
        $shiftName = (string) ($assignedShift['shift_name'] ?? 'Assigned Shift');

        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new DailyShiftHoursShortNotification(
                    $employee,
                    $workDateStr,
                    $shiftName,
                    $shiftTimeRange,
                    $requiredSeconds,
                    $workedSeconds,
                    $shortSeconds
                ));
            } catch (\Throwable $th) {
                Log::error("Failed to queue DailyShiftHoursShortNotification for recipient {$recipient->id}: " . $th->getMessage());
            }
        }

        return true;
    }

    /**
     * Safely extract HH:mm:ss time string from Carbon instance, ISO string, or time string.
     */
    protected function extractTimeString(mixed $time): ?string
    {
        if (!$time) {
            return null;
        }

        if ($time instanceof Carbon) {
            return $time->format('H:i:s');
        }

        $timeStr = (string) $time;

        if (preg_match('/(\d{2}:\d{2}(:\d{2})?)/', $timeStr, $matches)) {
            $extracted = $matches[1];
            return strlen($extracted) === 5 ? $extracted . ':00' : $extracted;
        }

        return null;
    }

    /**
     * Resolve unique recipients: Employee + Direct Reporter + Manager.
     */
    public function resolveNotificationRecipients(User $employee): Collection
    {
        $recipients = collect([$employee]);

        if ($employee->details?->reporter_id) {
            $reporter = $employee->details->reporter ?? User::find($employee->details->reporter_id);
            if ($reporter && $reporter->is_active && !$reporter->delete_status) {
                $recipients->push($reporter);
            }
        }

        if ($employee->details?->manager_id) {
            $manager = $employee->details->manager ?? User::find($employee->details->manager_id);
            if ($manager && $manager->is_active && !$manager->delete_status) {
                $recipients->push($manager);
            }
        }

        return $recipients->unique('id')->values();
    }
}

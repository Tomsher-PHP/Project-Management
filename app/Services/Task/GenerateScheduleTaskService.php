<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskSchedule;
use App\Models\User;
use App\Services\TaskServices;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GenerateScheduleTaskService
{
    public function __construct(
        protected TaskServices $taskServices,
    ) {}

    public function generateForDate(Carbon|string|null $date = null): int
    {
        $scheduledFor = $date instanceof Carbon
            ? $date->copy()->startOfDay()
            : Carbon::parse($date ?? 'today', config('constants.timezone'))->startOfDay();
        $generatedCount = 0;

        TaskSchedule::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $scheduledFor->toDateString())
            ->where(function ($query) use ($scheduledFor) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $scheduledFor->toDateString());
            })
            ->whereHas('project', fn ($query) => $query->whereNull('projects.deleted_at'))
            ->with('project')
            ->orderBy('id')
            ->chunkById(100, function ($taskSchedules) use ($scheduledFor, &$generatedCount) {
                foreach ($taskSchedules as $taskSchedule) {
                    if (! $this->isDueOn($taskSchedule, $scheduledFor)) {
                        continue;
                    }

                    try {
                        if ($this->generateTask($taskSchedule, $scheduledFor)) {
                            $generatedCount++;
                        }
                    } catch (\Throwable $exception) {
                        Log::error('Scheduled task generation failed.', [
                            'task_schedule_id' => $taskSchedule->id,
                            'scheduled_for_date' => $scheduledFor->toDateString(),
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return $generatedCount;
    }

    public function isDueOn(TaskSchedule $taskSchedule, Carbon $date): bool
    {
        return match ($taskSchedule->frequency_type) {
            TaskSchedule::FREQUENCY_DAILY => true,
            TaskSchedule::FREQUENCY_WEEKDAYS => in_array($date->isoWeekday(), $taskSchedule->week_days ?? [], true),
            TaskSchedule::FREQUENCY_WEEKLY => (int) $taskSchedule->weekly_day === $date->isoWeekday(),
            TaskSchedule::FREQUENCY_MONTHLY => in_array($date->day, $taskSchedule->month_days ?? [], true),
            default => false,
        };
    }

    private function generateTask(TaskSchedule $taskSchedule, Carbon $scheduledFor): ?Task
    {
        $scheduledDate = $scheduledFor->toDateString();

        if ($this->occurrenceExists($taskSchedule, $scheduledDate)) {
            return null;
        }

        $guard = Auth::guard();
        $previousUser = $guard->user();
        $scheduleOwner = $taskSchedule->added_by
            ? User::withTrashed()->find($taskSchedule->added_by)
            : null;

        try {
            if ($scheduleOwner) {
                $guard->setUser($scheduleOwner);
            } else {
                $guard->forgetUser();
            }

            $task = $this->taskServices->createQuickTask(
                $taskSchedule->project,
                $this->buildTaskPayload($taskSchedule, $scheduledFor)
            );

            $taskSchedule->forceFill([
                'last_generated_for' => $scheduledDate,
                'last_generated_at' => now(),
            ])->save();

            return $task;
        } catch (QueryException $exception) {
            if ($this->occurrenceExists($taskSchedule, $scheduledDate)) {
                return null;
            }

            throw $exception;
        } finally {
            if ($previousUser) {
                $guard->setUser($previousUser);
            } else {
                $guard->forgetUser();
            }
        }
    }

    private function buildTaskPayload(TaskSchedule $taskSchedule, Carbon $scheduledFor): array
    {
        return [
            'task_schedule_id' => $taskSchedule->id,
            'scheduled_for_date' => $scheduledFor->toDateString(),
            'request_type' => Task::REQUEST_TYPE_ASSIGNED,
            'project_milestone_id' => $taskSchedule->project_milestone_id,
            'project_sprint_id' => $taskSchedule->project_sprint_id,
            'name' => $taskSchedule->name,
            'description' => $taskSchedule->description,
            'task_type_id' => $taskSchedule->task_type_id,
            'task_mode_id' => $taskSchedule->task_mode_id,
            'priority' => $taskSchedule->priority,
            'current_assignee_id' => $taskSchedule->current_assignee_id,
            'due_date_time' => $scheduledFor->copy()->addHours((int) $taskSchedule->due_after_hours),
            'estimated_time_minutes' => intdiv((int) $taskSchedule->estimated_time_seconds, 60),
            'is_billable' => (bool) $taskSchedule->is_billable,
        ];
    }

    private function occurrenceExists(TaskSchedule $taskSchedule, string $scheduledDate): bool
    {
        return $taskSchedule->tasks()
            ->withTrashed()
            ->whereDate('scheduled_for_date', $scheduledDate)
            ->exists();
    }
}

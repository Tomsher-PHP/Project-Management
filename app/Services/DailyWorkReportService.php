<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskDeveloperNote;
use App\Models\TaskStatusHistory;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Models\UserShiftAssignment;
use Carbon\CarbonInterface;

class DailyWorkReportService
{
    public function generate(
        User $employee,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $userId = $employee->id;
        $reportDate = $start->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Employee Assigned Shift
        |--------------------------------------------------------------------------
        |
        | Retrieve the shift assignment active on the report date.
        |
        */

        $shiftAssignment = UserShiftAssignment::query()
            ->with('shift')
            ->where('user_id', $userId)
            ->whereDate('date_from', '<=', $reportDate)
            ->where(function ($query) use ($reportDate) {
                $query->whereNull('date_to')
                    ->orWhereDate('date_to', '>=', $reportDate);
            })
            ->orderByDesc('date_from')
            ->first();

        $assignedShift = null;

        if ($shiftAssignment) {
            $assignedShift = [
                'name' => $shiftAssignment->shift_name
                    ?? $shiftAssignment->shift?->name,
                'start' => $shiftAssignment->time_from,
                'end' => $shiftAssignment->time_to,
                // 'break' => $shiftAssignment->break_duration,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Currently Assigned Tasks
        |--------------------------------------------------------------------------
        */

        $currentTasks = Task::query()
            ->where('current_assignee_id', $userId)
            ->with([
                'project',
                'status',
                'developerNotes',
            ])
            ->whereHas('project', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Time Logs During Reporting Period
        |--------------------------------------------------------------------------
        */

        $timeLogs = TaskTimeLog::query()
            ->with(['task.project'])
            ->where('user_id', $userId)
            ->whereBetween('started_at', [$start, $end])
            ->whereHas('task', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Tasks With Time Logged
        |--------------------------------------------------------------------------
        */

        $loggedTaskIds = $timeLogs
            ->pluck('task_id')
            ->filter()
            ->unique()
            ->values();

        $loggedTasks = Task::query()
            ->whereIn('id', $loggedTaskIds)
            ->with([
                'project',
                'status',
                'developerNotes',
            ])
            ->whereHas('project', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Pending Tasks
        |--------------------------------------------------------------------------
        |
        | Total Tasks:
        | Todo tasks due today or earlier.
        |
        | Pending:
        | Todo tasks due specifically today.
        |
        */

        $pendingTasks = $currentTasks->filter(
            function ($task) use ($reportDate) {
                $dueDate = $task->due_date_time;

                return $task->status?->type === 'pending'
                    && $dueDate
                    && $dueDate->toDateString() <= $reportDate;
            }
        );

        $pendingDueToday = $pendingTasks->filter(
            function ($task) use ($reportDate) {
                return $task->due_date_time->toDateString()
                    === $reportDate;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | In Progress Tasks
        |--------------------------------------------------------------------------
        |
        | Only count active tasks that have time logged
        | during the reporting period.
        |
        */

        $inProgressTasks = $currentTasks->filter(
            function ($task) use ($loggedTaskIds) {
                return $task->status?->type === 'active'
                    && $loggedTaskIds->contains($task->id);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Overdue Tasks
        |--------------------------------------------------------------------------
        |
        | Tasks due today or earlier that are not completed.
        |
        */

        $overdueTasks = $currentTasks->filter(
            function ($task) use ($reportDate) {
                $dueDate = $task->due_date_time;

                return $dueDate
                    && $dueDate->toDateString() <= $reportDate
                    && ! $task->status?->is_completed;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Task Status Changes During Reporting Period
        |--------------------------------------------------------------------------
        */

        $statusChanges = TaskStatusHistory::query()
            ->with([
                'task.project',
                'status',
            ])
            ->where('added_by', $userId)
            ->whereBetween('added_at', [$start, $end])
            ->whereHas('task', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Employee's Completed Task Activity
        |--------------------------------------------------------------------------
        |
        | Retained for the daily activity section.
        |
        */

        $completedTaskIds = $statusChanges
            ->filter(
                fn ($history) => $history->status?->is_completed
            )
            ->pluck('task_id')
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Completed Tasks With Time Logged
        |--------------------------------------------------------------------------
        |
        | A task is counted as completed when:
        | 1. It has a time log during the reporting period.
        | 2. It was moved to a completed status during the period.
        |
        | No added_by restriction here because a manager or another
        | authorized user may complete the task.
        |
        */

        $completedLoggedTaskIds = TaskStatusHistory::query()
            ->whereIn('task_id', $loggedTaskIds)
            ->whereBetween('added_at', [$start, $end])
            ->whereHas('status', function ($query) {
                $query->where('is_completed', true);
            })
            ->whereHas('task', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->pluck('task_id')
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Comments During Reporting Period
        |--------------------------------------------------------------------------
        */

        $comments = TaskComment::query()
            ->with(['task.project'])
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Developer Notes During Reporting Period
        |--------------------------------------------------------------------------
        */

        $developerNotes = TaskDeveloperNote::query()
            ->with(['task.project'])
            ->where('created_by', $userId)
            ->whereBetween('updated_at', [$start, $end])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | New Task Assignments
        |--------------------------------------------------------------------------
        */

        $newAssignments = $employee->taskAssignmentLogs()
            ->with(['task.project'])
            ->whereBetween('assigned_to', [$start, $end])
            ->get();

        $totalLoggedSeconds = (int) $timeLogs->sum(
                fn ($log) => (int) ($log->duration_seconds ?? 0)
            );

        /*
        |--------------------------------------------------------------------------
        | Report Data
        |--------------------------------------------------------------------------
        */

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
            ],

            'period' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Shift Information
            |--------------------------------------------------------------------------
            */

            'shift' => $assignedShift,

            /*
            |--------------------------------------------------------------------------
            | Workload Overview
            |--------------------------------------------------------------------------
            */

            'current_workload' => [
                'total' => $pendingTasks->count(),

                'completed' => $completedLoggedTaskIds->count(),

                'in_progress' => $inProgressTasks->count(),

                'pending' => $pendingDueToday->count(),

                'overdue' => $overdueTasks->count(),

                // Tasks with time logged during the reporting period.
                'tasks' => $loggedTasks->map(
                    fn ($task) => $this->taskSummary($task)
                )->values()->all(),

                // Todo tasks due today or earlier.
                'pending_tasks' => $pendingTasks->map(
                    fn ($task) => $this->taskSummary($task)
                )->values()->all(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Daily Activity
            |--------------------------------------------------------------------------
            */

            'daily_activity' => [
                'completed_task_ids' => $completedTaskIds->all(),

                'status_changes' => $statusChanges->map(
                    fn ($history) => [
                        'task_id' => $history->task_id,
                        'task_name' => $history->task?->name,
                        'project_name' => $history->task?->project?->name,
                        'status' => $history->status?->name,
                        'changed_at' => $history->added_at?->toIso8601String(),
                        'remarks' => $history->remarks,
                    ]
                )->values()->all(),

                'new_assignments' => $newAssignments->map(
                    fn ($log) => [
                        'task_id' => $log->task?->id,
                        'task_name' => $log->task?->name,
                        'project_name' => $log->task?->project?->name,
                        'assigned_at' => $log->assigned_to,
                    ]
                )->values()->all(),
            ],



            /*
            |--------------------------------------------------------------------------
            | Time Logs
            |--------------------------------------------------------------------------
            */

            'time_logs' => [
                'total_seconds' => $totalLoggedSeconds,

                'entries' => $timeLogs->map(function ($log) {
                    return [
                        'task_name' => $log->task?->name,
                        'project_name' => $log->task?->project?->name,
                        'started_at' => $log->started_at,
                        'ended_at' => $log->ended_at,
                        'duration_seconds' => (int) ($log->duration_seconds ?? 0),
                        'note' => $log->note,
                    ];
                })->values()->toArray(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Comments
            |--------------------------------------------------------------------------
            */

            'comments' => $comments->map(
                fn ($comment) => [
                    'task_name' => $comment->task?->name,
                    'project_name' => $comment->task?->project?->name,
                    'comment' => $comment->comment,
                    'created_at' => $comment->created_at?->toIso8601String(),
                ]
            )->values()->all(),

            /*
            |--------------------------------------------------------------------------
            | Developer Notes
            |--------------------------------------------------------------------------
            */

            'developer_notes' => $developerNotes->map(
                fn ($note) => [
                    'task_name' => $note->task?->name,
                    'project_name' => $note->task?->project?->name,
                    'note' => $note->note,
                    'updated_at' => $note->updated_at?->toIso8601String(),
                ]
            )->values()->all(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Task Summary
    |--------------------------------------------------------------------------
    */

    private function taskSummary(Task $task): array
    {
        $dueDate = $task->due_date_time;
        $completedAt = $task->completed_at;

        $completionPerformance = null;
        $completionDifferenceSeconds = null;

        if (
            $task->status?->is_completed
            && $dueDate
            && $completedAt
        ) {
            $completionDifferenceSeconds = $dueDate->getTimestamp()
                - $completedAt->getTimestamp();

            if ($completionDifferenceSeconds > 0) {
                $completionPerformance = 'early';
            } elseif ($completionDifferenceSeconds === 0) {
                $completionPerformance = 'on_time';
            } else {
                $completionPerformance = 'late';
            }
        }

        // Get the latest developer note.
        $latestDeveloperNote = $task->developerNotes
            ->sortByDesc('updated_at')
            ->first();

        return [
            'id' => $task->id,
            'name' => $task->name,
            'project' => $task->project?->name,
            'status' => $task->status?->name,
            'due_date' => $dueDate?->toIso8601String(),
            'completed_at' => $completedAt?->toIso8601String(),
            'completion_performance' => $completionPerformance,
            'completion_difference_seconds' => $completionDifferenceSeconds,
            'estimated_time_seconds' => (int) $task->estimated_time_seconds,
            'actual_time_seconds' => (int) $task->actual_time_seconds,
            'developer_note' => $latestDeveloperNote?->note,
        ];
    }
}

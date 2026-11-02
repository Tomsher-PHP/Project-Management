<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskDeveloperNote;
use App\Models\TaskStatusHistory;
use App\Models\TaskTimeLog;
use App\Models\User;
use Carbon\CarbonInterface;

class DailyWorkReportService
{
    public function generate(
        User $employee,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $userId = $employee->id;

        $currentTasks = Task::query()
            ->where('current_assignee_id', $userId)
            ->with(['project', 'status'])
            ->whereHas('project', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        $completedTasks = $currentTasks->filter(
            fn ($task) => $task->status?->is_completed
        );

        $inProgressTasks = $currentTasks->filter(
            fn ($task) => $task->status?->type === 'active'
        );

        $pendingTasks = $currentTasks->filter(
            fn ($task) => $task->status?->type === 'pending'
        );

        $overdueTasks = $currentTasks->filter(
            fn ($task) =>
                $task->due_date_time
                && $task->due_date_time->lt(now())
                && ! $task->status?->is_completed
        );

        $statusChanges = TaskStatusHistory::query()
            ->with(['task.project', 'status'])
            ->where('added_by', $userId)
            ->whereBetween('added_at', [$start, $end])
            ->whereHas('task', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        $completedTaskIds = $statusChanges
            ->filter(fn ($history) => $history->status?->is_completed)
            ->pluck('task_id')
            ->unique()
            ->values();

        $timeLogs = TaskTimeLog::query()
            ->with(['task.project'])
            ->where('user_id', $userId)
            ->whereBetween('started_at', [$start, $end])
            ->whereHas('task', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        $comments = TaskComment::query()
            ->with(['task.project'])
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $developerNotes = TaskDeveloperNote::query()
            ->with(['task.project'])
            ->where('created_by', $userId)
            ->whereBetween('updated_at', [$start, $end])
            ->get();

        $newAssignments = $employee->taskAssignmentLogs()
            ->with(['task.project'])
            ->whereBetween('assigned_to', [$start, $end])
            ->get();

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

            'current_workload' => [
                'total' => $currentTasks->count(),
                'completed' => $completedTasks->count(),
                'in_progress' => $inProgressTasks->count(),
                'pending' => $pendingTasks->count(),
                'overdue' => $overdueTasks->count(),
                'tasks' => $currentTasks->map(
                    fn ($task) => $this->taskSummary($task)
                )->values()->all(),
            ],

            'daily_activity' => [
                'completed_task_ids' => $completedTaskIds->all(),

                'status_changes' => $statusChanges->map(fn ($history) => [
                    'task_id' => $history->task_id,
                    'task_name' => $history->task?->name,
                    'project_name' => $history->task?->project?->name,
                    'status' => $history->status?->name,
                    'changed_at' => $history->added_at?->toIso8601String(),
                    'remarks' => $history->remarks,
                ])->values()->all(),

                'new_assignments' => $newAssignments->map(fn ($log) => [
                    'task_id' => $log->task?->id,
                    'task_name' => $log->task?->name,
                    'project_name' => $log->task?->project?->name,
                    'assigned_at' => $log->assigned_to,
                ])->values()->all(),
            ],

            'time_logs' => [
                'total_seconds' => $timeLogs->sum(
                    fn ($log) => (int) $log->duration_seconds
                ),

                'entries' => $timeLogs->map(fn ($log) => [
                    'task_name' => $log->task?->name,
                    'project_name' => $log->task?->project?->name,
                    'started_at' => $log->started_at?->toIso8601String(),
                    'ended_at' => $log->ended_at?->toIso8601String(),
                    'duration_seconds' => (int) $log->duration_seconds,
                    'note' => $log->note,
                ])->values()->all(),
            ],

            'comments' => $comments->map(fn ($comment) => [
                'task_name' => $comment->task?->name,
                'project_name' => $comment->task?->project?->name,
                'comment' => $comment->comment,
                'created_at' => $comment->created_at?->toIso8601String(),
            ])->values()->all(),

            'developer_notes' => $developerNotes->map(fn ($note) => [
                'task_name' => $note->task?->name,
                'project_name' => $note->task?->project?->name,
                'note' => $note->note,
                'updated_at' => $note->updated_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    private function taskSummary(Task $task): array
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'project' => $task->project?->name,
            'status' => $task->status?->name,
            'due_date' => $task->due_date_time?->toIso8601String(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'estimated_time_seconds' => (int) $task->estimated_time_seconds,
            'actual_time_seconds' => (int) $task->actual_time_seconds,
        ];
    }
}
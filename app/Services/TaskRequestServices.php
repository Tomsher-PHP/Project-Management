<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TaskRequestServices
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function getRequestsForUser(User $user, int $perPage, string $status = 'pending', array $filters = []): LengthAwarePaginator
    {
        $query = $this->visibleRequestQuery($user)
            ->where('request_status', $status)
            ->with([
                'project:id,name,project_code',
                'projectMilestone:id,name,owner_id',
                'projectSprint:id,name',
                'currentAssignee:id,name',
                'status:id,name,color,type,is_completed',
                'taskType:id,name,code,color',
                'taskMode:id,name,code,color',
                'approvedBy:id,name',
                'rejectedBy:id,name',
            ])
            ->withExists([
                'currentAssignee as is_self_requested' => fn(Builder $query) => $query->whereKey($user->id),
            ]);

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters);

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getFilterOptions(User $user): array
    {
        $query = $this->visibleRequestQuery($user);
        $projectIds = (clone $query)->distinct()->pluck('project_id')->filter();
        $userIds = (clone $query)->distinct()->pluck('current_assignee_id')->filter();

        return [
            'projects' => $projectIds->isEmpty()
                ? collect()
                : Project::query()->whereIn('id', $projectIds)->orderBy('name')->get(['id', 'name']),
            'users' => $userIds->isEmpty()
                ? collect()
                : User::query()->whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function handleAction(User $user, Task $task, string $action, ?string $reason = null): void
    {
        abort_unless($this->canHandleRequest($user, $task), Response::HTTP_FORBIDDEN);

        DB::transaction(function () use ($user, $task, $action, $reason) {
            if ($action === 'approve') {
                $this->approve($user, $task);

                return;
            }

            $this->reject($user, $task, (string) $reason);
        });
    }

    public function handleBulkAction(User $user, array $taskIds, string $action, ?string $reason = null): int
    {
        $taskIds = collect($taskIds)
            ->map(fn($taskId) => (int) $taskId)
            ->unique()
            ->values();

        abort_if($taskIds->isEmpty(), Response::HTTP_UNPROCESSABLE_ENTITY, 'Please select at least one task request.');

        $tasks = $this->accountableRequestQuery($user)
            ->whereKey($taskIds)
            ->where('request_status', 'pending')
            ->get();

        abort_unless($tasks->count() === $taskIds->count(), Response::HTTP_FORBIDDEN);

        DB::transaction(function () use ($user, $tasks, $action, $reason) {
            foreach ($tasks as $task) {
                if ($action === 'approve') {
                    $this->approve($user, $task);

                    continue;
                }

                $this->reject($user, $task, (string) $reason);
            }
        });

        return $tasks->count();
    }

    public function canHandleRequest(User $user, Task $task): bool
    {
        return $this->accountableRequestQuery($user)
            ->whereKey($task->id)
            ->where('request_status', 'pending')
            ->exists();
    }

    private function visibleRequestQuery(User $user): Builder
    {
        $query = Task::query()->where('request_type', Task::REQUEST_TYPE_SELF);

        if ($user->is_super_admin) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query
                ->where('current_assignee_id', $user->id)
                ->orWhere(function (Builder $accountableQuery) use ($user) {
                    $this->applyAccountableUserScope($accountableQuery, $user);
                });
        });
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['project_id'] ?? null, fn(Builder $query, $projectIds) => $query->whereIn('project_id', (array) $projectIds))
            ->when($filters['current_assignee_id'] ?? null, fn(Builder $query, $userIds) => $query->whereIn('current_assignee_id', (array) $userIds));

        if (! blank($filters['search'] ?? null)) {
            $this->applyNameFilter(
                $query,
                (string) $filters['search'],
                (string) ($filters['search_condition'] ?? 'contains')
            );
        }
    }

    private function applyNameFilter(Builder $query, string $search, string $condition): void
    {
        match ($condition) {
            'starts_with' => $query->where('tasks.name', 'like', $search . '%'),
            'ends_with' => $query->where('tasks.name', 'like', '%' . $search),
            'not_contains' => $query->where('tasks.name', 'not like', '%' . $search . '%'),
            default => $query->where('tasks.name', 'like', '%' . $search . '%'),
        };
    }

    private function applySort(Builder $query, array $filters): void
    {
        $direction = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($filters['sort_by'] ?? null) {
            'name' => $query->orderBy('tasks.name', $direction),
            'project.name' => $query
                ->leftJoin('projects as request_projects', 'request_projects.id', '=', 'tasks.project_id')
                ->select('tasks.*')
                ->orderBy('request_projects.name', $direction),
            'currentAssignee.name' => $query
                ->leftJoin('users as request_users', 'request_users.id', '=', 'tasks.current_assignee_id')
                ->select('tasks.*')
                ->orderBy('request_users.name', $direction),
            'due_date_time' => $query->orderBy('tasks.due_date_time', $direction),
            default => $query
                ->orderByRaw("CASE tasks.request_status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")
                ->latest(),
        };
    }

    private function accountableRequestQuery(User $user): Builder
    {
        $query = Task::query()->where('request_type', Task::REQUEST_TYPE_SELF);

        if ($user->is_super_admin) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $this->applyAccountableUserScope($query, $user);
        });
    }

    private function applyAccountableUserScope(Builder $query, User $user): void
    {
        $query
            ->whereHas('currentAssignee.details', function (Builder $detailsQuery) use ($user) {
                $detailsQuery
                    ->where('reporter_id', $user->id)
                    ->orWhere('manager_id', $user->id);
            })
            ->orWhereHas('project.teamLeader', function (Builder $teamLeaderQuery) use ($user) {
                $teamLeaderQuery->whereKey($user->id);
            })
            ->orWhereHas('projectMilestone', function (Builder $milestoneQuery) use ($user) {
                $milestoneQuery->where('owner_id', $user->id);
            });
    }

    private function approve(User $user, Task $task): void
    {
        $approvedAt = now();

        $task->update([
            'request_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => $approvedAt,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->where('user_id', $task->current_assignee_id)
            ->update([
                'is_approved' => true,
                'approved_by' => $user->id,
                'approved_at' => $approvedAt,
            ]);

        app(ProjectTimeService::class)->recalculateByTask($task->id);

        $this->notificationService->notifyTaskRequestReviewed($task, $user, 'approve');
    }

    private function reject(User $user, Task $task, string $reason): void
    {
        $reason = trim($reason);
        $this->stopRunningTimersForRejectedTask($task);

        $task->update([
            'request_status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->notificationService->notifyTaskRequestReviewed($task, $user, 'reject', $reason);
    }

    // Force stop any running timers for the task when it's rejected, and log the time until rejection
    private function stopRunningTimersForRejectedTask(Task $task): void
    {
        $runningLogs = TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->where('is_running', true)
            ->get();

        if ($runningLogs->isEmpty()) {
            return;
        }

        $now = now();
        $totalDuration = 0;

        foreach ($runningLogs as $log) {
            $duration = max(0, $log->started_at?->diffInSeconds($now) ?? 0);

            $log->update([
                'ended_at' => $now,
                'duration_seconds' => $duration,
                'is_running' => false,
            ]);

            if ($log->task_assignment_log_id) {
                TaskAssignmentLog::query()
                    ->whereKey($log->task_assignment_log_id)
                    ->increment('worked_time_seconds', $duration);
            }

            $totalDuration += $duration;
        }

        if ($totalDuration > 0) {
            $task->increment('actual_time_seconds', $totalDuration);
        }
    }
}

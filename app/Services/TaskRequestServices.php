<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TaskRequestServices
{
    public function getRequestsForUser(User $user, int $perPage, string $status = 'pending'): LengthAwarePaginator
    {
        return $this->visibleRequestQuery($user)
            ->where('request_status', $status)
            ->with([
                'project:id,name,project_code',
                'projectModule:id,name,owner_id',
                'projectSprint:id,name',
                'currentAssignee:id,name',
                'status:id,name,color',
                'taskType:id,name,code,color',
                'taskMode:id,name,code,color',
            ])
            ->withExists([
                'currentAssignee as is_self_requested' => fn(Builder $query) => $query->whereKey($user->id),
            ])
            ->orderByRaw("CASE request_status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
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

    private function canHandleRequest(User $user, Task $task): bool
    {
        return $this->accountableRequestQuery($user)
            ->whereKey($task->id)
            ->exists();
    }

    private function visibleRequestQuery(User $user): Builder
    {
        return Task::query()
            ->where('request_type', 'self')
            ->where(function (Builder $query) use ($user) {
                $query
                    ->where('current_assignee_id', $user->id)
                    ->orWhere(function (Builder $accountableQuery) use ($user) {
                        $this->applyAccountableUserScope($accountableQuery, $user);
                    });
            });
    }

    private function accountableRequestQuery(User $user): Builder
    {
        return Task::query()
            ->where('request_type', 'self')
            ->where(function (Builder $query) use ($user) {
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
            ->orWhereHas('projectModule', function (Builder $moduleQuery) use ($user) {
                $moduleQuery->where('owner_id', $user->id);
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
    }

    private function reject(User $user, Task $task, string $reason): void
    {
        $task->update([
            'request_status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => trim($reason),
        ]);
    }
}

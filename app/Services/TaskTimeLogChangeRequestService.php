<?php

namespace App\Services;

use App\Models\TaskTimeLog;
use App\Models\TaskTimeLogChangeRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskTimeLogChangeRequestService
{
    public function getRequestsForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->visibleRequestQuery($user)
            ->with([
                'user:id,name',
                'user.primaryAttachment',
                'timeLog:id,task_id,user_id,started_at,ended_at,duration_seconds,is_running',
                'timeLog.task:id,name',
                'approver:id,name',
                'rejector:id,name',
            ])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(User $user, TaskTimeLog $timeLog, array $payload): TaskTimeLogChangeRequest
    {
        return TaskTimeLogChangeRequest::query()->create([
            'task_time_log_id' => $timeLog->id,
            'user_id' => $user->id,
            'old_started_at' => $timeLog->started_at,
            'old_ended_at' => $timeLog->ended_at,
            'new_started_at' => $payload['new_started_at'],
            'new_ended_at' => $payload['new_ended_at'],
            'reason' => trim((string) ($payload['reason'] ?? '')),
            'status' => 'pending',
        ]);
    }

    public function handleAction(User $user, TaskTimeLogChangeRequest $changeRequest, string $action, ?string $reason = null): void
    {
        abort_unless($this->canHandleRequest($user, $changeRequest), Response::HTTP_FORBIDDEN);

        if (! $changeRequest->isPending()) {
            throw ValidationException::withMessages([
                'change_request' => 'Only pending time log change requests can be reviewed.',
            ]);
        }

        DB::transaction(function () use ($user, $changeRequest, $action, $reason) {
            if ($action === 'approve') {
                $this->approve($user, $changeRequest);

                return;
            }

            $this->reject($user, $changeRequest, (string) $reason);
        });
    }

    private function visibleRequestQuery(User $user): Builder
    {
        if ($user->is_super_admin) {
            return TaskTimeLogChangeRequest::query();
        }

        $accessibleUserIds = User::query()
            ->accessibleBy($user)
            ->select('users.id');

        return TaskTimeLogChangeRequest::query()
            ->whereIn('user_id', $accessibleUserIds);
    }

    private function canHandleRequest(User $user, TaskTimeLogChangeRequest $changeRequest): bool
    {
        return $this->visibleRequestQuery($user)
            ->whereKey($changeRequest->id)
            ->exists();
    }

    private function approve(User $user, TaskTimeLogChangeRequest $changeRequest): void
    {
        $timeLog = $changeRequest->timeLog()->first();

        if (! $timeLog) {
            throw ValidationException::withMessages([
                'change_request' => 'The selected time log no longer exists.',
            ]);
        }

        if ((bool) $timeLog->is_running) {
            throw ValidationException::withMessages([
                'change_request' => 'Running time logs cannot be updated from a change request.',
            ]);
        }

        $newStartedAt = $changeRequest->new_started_at;
        $newEndedAt = $changeRequest->new_ended_at;

        if (! $newStartedAt || ! $newEndedAt || ! $newEndedAt->greaterThan($newStartedAt)) {
            throw ValidationException::withMessages([
                'change_request' => 'The requested time range is invalid.',
            ]);
        }

        if ($this->hasOverlappingLog($timeLog, $newStartedAt, $newEndedAt)) {
            throw ValidationException::withMessages([
                'change_request' => 'The requested time overlaps with another time log.',
            ]);
        }

        $approvedAt = now();

        $timeLog->update([
            'started_at' => $newStartedAt,
            'ended_at' => $newEndedAt,
            'duration_seconds' => $newStartedAt->diffInSeconds($newEndedAt),
        ]);

        $changeRequest->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => $approvedAt,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    private function reject(User $user, TaskTimeLogChangeRequest $changeRequest, string $reason): void
    {
        $changeRequest->update([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => trim($reason),
        ]);
    }

    private function hasOverlappingLog(TaskTimeLog $timeLog, $newStartedAt, $newEndedAt): bool
    {
        return TaskTimeLog::query()
            ->where('user_id', $timeLog->user_id)
            ->whereKeyNot($timeLog->id)
            ->where(function ($query) use ($newStartedAt, $newEndedAt) {
                $query
                    ->where(function ($endedQuery) use ($newStartedAt, $newEndedAt) {
                        $endedQuery
                            ->whereNotNull('ended_at')
                            ->where('started_at', '<', $newEndedAt)
                            ->where('ended_at', '>', $newStartedAt);
                    })
                    ->orWhere(function ($runningQuery) use ($newEndedAt) {
                        $runningQuery
                            ->whereNull('ended_at')
                            ->where('started_at', '<', $newEndedAt);
                    });
            })
            ->exists();
    }
}

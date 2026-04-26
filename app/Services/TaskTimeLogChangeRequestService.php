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
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function getRequestsForUser(User $user, int $perPage, string $status = 'pending', array $filters = []): LengthAwarePaginator
    {
        $query = $this->visibleRequestQuery($user)
            ->where('status', $status)
            ->with([
                'user:id,name',
                'user.primaryAttachment',
                'timeLog:id,task_id,user_id,started_at,ended_at,duration_seconds,is_running',
                'timeLog.task:id,name',
                'approver:id,name',
                'rejector:id,name',
            ]);

        $this->applyFilters($query, $filters);

        return $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getFilterOptions(User $user): array
    {
        $query = $this->visibleRequestQuery($user);
        $userIds = (clone $query)->distinct()->pluck('user_id')->filter();

        return [
            'users' => $userIds->isEmpty()
                ? collect()
                : User::query()->whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function create(User $user, TaskTimeLog $timeLog, array $payload): TaskTimeLogChangeRequest
    {
        $changeRequest = TaskTimeLogChangeRequest::query()->create([
            'task_time_log_id' => $timeLog->id,
            'user_id' => $user->id,
            'old_started_at' => $timeLog->started_at,
            'old_ended_at' => $timeLog->ended_at,
            'new_started_at' => $payload['new_started_at'],
            'new_ended_at' => $payload['new_ended_at'],
            'reason' => trim((string) ($payload['reason'] ?? '')),
            'status' => 'pending',
        ]);

        $this->notificationService->notifyTaskTimeLogChangeRequestCreated($changeRequest);

        return $changeRequest;
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

    public function handleBulkAction(User $user, array $changeRequestIds, string $action, ?string $reason = null): int
    {
        $changeRequestIds = collect($changeRequestIds)
            ->map(fn($changeRequestId) => (int) $changeRequestId)
            ->unique()
            ->values();

        abort_if($changeRequestIds->isEmpty(), Response::HTTP_UNPROCESSABLE_ENTITY, 'Please select at least one time log change request.');

        $changeRequests = $this->visibleRequestQuery($user)
            ->whereIn('id', $changeRequestIds)
            ->where('status', 'pending')
            ->get();

        abort_unless($changeRequests->count() === $changeRequestIds->count(), Response::HTTP_FORBIDDEN);

        DB::transaction(function () use ($user, $changeRequests, $action, $reason) {
            foreach ($changeRequests as $changeRequest) {
                if ($action === 'approve') {
                    $this->approve($user, $changeRequest);

                    continue;
                }

                $this->reject($user, $changeRequest, (string) $reason);
            }
        });

        return $changeRequests->count();
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

    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when(
            $filters['user_id'] ?? null,
            fn(Builder $builder, $userIds) => $builder->whereIn('user_id', (array) $userIds)
        );
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

        $this->notificationService->notifyTaskTimeLogChangeRequestReviewed($changeRequest, $user, 'approve');
    }

    private function reject(User $user, TaskTimeLogChangeRequest $changeRequest, string $reason): void
    {
        $reason = trim($reason);

        $changeRequest->update([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->notificationService->notifyTaskTimeLogChangeRequestReviewed($changeRequest, $user, 'reject', $reason);
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

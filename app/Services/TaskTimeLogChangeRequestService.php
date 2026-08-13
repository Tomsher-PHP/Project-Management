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
    public function __construct(private readonly NotificationService $notificationService) {}

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
            ])
            ->withExists([
                'user as is_self_requested' => fn(Builder $query) => $query->whereKey($user->id),
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

    public function updatePendingRequest(User $user, TaskTimeLogChangeRequest $changeRequest, TaskTimeLog $timeLog, array $payload): TaskTimeLogChangeRequest
    {
        abort_unless(
            $changeRequest->isPending()
                && (int) $changeRequest->user_id === (int) $user->id
                && (int) $changeRequest->task_time_log_id === (int) $timeLog->id,
            Response::HTTP_FORBIDDEN
        );

        $changeRequest->update([
            'new_started_at' => $payload['new_started_at'],
            'new_ended_at' => $payload['new_ended_at'],
            'reason' => trim((string) ($payload['reason'] ?? '')),
        ]);

        return $changeRequest->refresh();
    }

    public function handleAction(User $user, TaskTimeLogChangeRequest $changeRequest, string $action, ?string $reason = null, ?array $approvalTimeRange = null): void
    {
        abort_unless($this->canHandleRequest($user, $changeRequest), Response::HTTP_FORBIDDEN);
        if (! $changeRequest->isPending()) {
            throw ValidationException::withMessages([
                'change_request' => 'Only pending time log change requests can be reviewed.',
            ]);
        }

        DB::transaction(function () use ($user, $changeRequest, $action, $reason, $approvalTimeRange) {
            if ($action === 'approve') {
                $this->approve($user, $changeRequest, $approvalTimeRange);

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
            ->where('user_id', '!=', $user->id)
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

        $accessibleUserIds = app(UserService::class)->getRequestAccessibleUsers($user);

        return TaskTimeLogChangeRequest::query()->whereIn('user_id', $accessibleUserIds);
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
        if ((int) $changeRequest->user_id === (int) $user->id) {
            return false;
        }

        return $this->visibleRequestQuery($user)
            ->whereKey($changeRequest->id)
            ->exists();
    }

    private function approve(User $user, TaskTimeLogChangeRequest $changeRequest, ?array $approvalTimeRange = null): void
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

        $isEditedApproval = $approvalTimeRange !== null;
        $newStartedAt = $approvalTimeRange['new_started_at'] ?? $changeRequest->new_started_at;
        $newEndedAt = $approvalTimeRange['new_ended_at'] ?? $changeRequest->new_ended_at;

        if (! $newStartedAt || ! $newEndedAt || ! $newEndedAt->greaterThan($newStartedAt)) {
            throw ValidationException::withMessages([
                $isEditedApproval ? 'new_ended_at' : 'change_request' => $isEditedApproval
                    ? 'The requested end date and time must be later than the requested start date and time.'
                    : 'The requested time range is invalid.',
            ]);
        }

        if ($isEditedApproval && $newEndedAt->isFuture()) {
            throw ValidationException::withMessages([
                'new_ended_at' => 'The requested end date and time cannot be in the future.',
            ]);
        }

        $this->validateOverlaps($timeLog, $changeRequest, $newStartedAt, $newEndedAt, $isEditedApproval);

        $approvedAt = now();

        $timeLog->update([
            'started_at' => $newStartedAt,
            'ended_at' => $newEndedAt,
            'duration_seconds' => $newStartedAt->diffInSeconds($newEndedAt),
        ]);

        $changeRequest->update([
            'new_started_at' => $newStartedAt,
            'new_ended_at' => $newEndedAt,
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

    private function validateOverlaps(TaskTimeLog $timeLog, TaskTimeLogChangeRequest $changeRequest, $newStartedAt, $newEndedAt, bool $validateRequestOverlaps): void
    {
        $applyTimeRangeOverlapScope = function ($query) use ($newStartedAt, $newEndedAt) {
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
        };

        $hasUserOverlap = TaskTimeLog::query()
            ->where('user_id', $timeLog->user_id)
            ->whereKeyNot($timeLog->id)
            ->where($applyTimeRangeOverlapScope)
            ->exists();

        if ($hasUserOverlap) {
            throw ValidationException::withMessages([
                $validateRequestOverlaps ? 'new_ended_at' : 'change_request' => $validateRequestOverlaps
                    ? 'The requested time overlaps with another time log for this user.'
                    : 'The requested time overlaps with another time log.',
            ]);
        }

        if (! $validateRequestOverlaps) {
            return;
        }

        $hasTaskOverlap = TaskTimeLog::query()
            ->where('task_id', $timeLog->task_id)
            ->whereKeyNot($timeLog->id)
            ->where($applyTimeRangeOverlapScope)
            ->exists();

        if ($hasTaskOverlap) {
            throw ValidationException::withMessages([
                'new_ended_at' => 'The requested time range is already logged by another user.',
            ]);
        }

        $hasPendingRequestOverlap = TaskTimeLogChangeRequest::query()
            ->whereHas('timeLog', fn($query) => $query->where('task_id', $timeLog->task_id))
            ->where('status', 'pending')
            ->whereKeyNot($changeRequest->id)
            ->where(function ($query) use ($newStartedAt, $newEndedAt) {
                $query
                    ->where(function ($endedQuery) use ($newStartedAt, $newEndedAt) {
                        $endedQuery
                            ->whereNotNull('new_ended_at')
                            ->where('new_started_at', '<', $newEndedAt)
                            ->where('new_ended_at', '>', $newStartedAt);
                    })
                    ->orWhere(function ($runningQuery) use ($newEndedAt) {
                        $runningQuery
                            ->whereNull('new_ended_at')
                            ->where('new_started_at', '<', $newEndedAt);
                    });
            })
            ->exists();

        if ($hasPendingRequestOverlap) {
            throw ValidationException::withMessages([
                'new_ended_at' => 'Another pending request exists in the requested time range.',
            ]);
        }
    }
}

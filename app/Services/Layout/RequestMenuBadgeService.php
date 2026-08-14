<?php

namespace App\Services\Layout;

use App\Models\BreakWorkRequest;
use App\Models\HandoffRequest;
use App\Models\Task;
use App\Models\TaskExtendTimeRequest;
use App\Models\TaskTimeLogChangeRequest;
use App\Models\User;
use App\Services\TaskTimeExtendService;
use App\Services\TaskTimeLogChangeRequestService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;

class RequestMenuBadgeService
{
    public function getPendingCountsForUser(?User $user): array
    {
        if (! $user) {
            return $this->empty();
        }

        $taskRequests = $this->taskRequestCount($user);

        $taskTime = $this->taskTimeChangeRequestCount($user);

        $taskHandoff = $user->canAny(['handoff_request.view', 'handoff_request.view_all'])
            ? $this->handoffRequestCount($user)
            : 0;

        $breakRequests = $this->breakRequestCount($user);

        $taskTimeExtendRequests = $this->taskTimeExtendRequestCount($user);

        return [
            'task_requests' => $taskRequests,
            'task_time' => $taskTime,
            'task_handoff' => $taskHandoff,
            'break_requests' => $breakRequests,
            'task_time_extend_requests' => $taskTimeExtendRequests,
            'has_any_pending' => ($taskRequests + $taskTime + $taskHandoff + $breakRequests + $taskTimeExtendRequests) > 0,
        ];
    }

    /**
     * -----------------------Count methods
     */
    private function taskRequestCount(User $user): int
    {
        return $this->visibleTaskRequestQuery($user)
            ->where('request_status', Task::REQUEST_PENDING)
            ->count();
    }

    private function taskTimeChangeRequestCount(User $user): int
    {
        return $this->visibleTaskTimeChangeRequestQuery($user)
            ->where('status', 'pending')
            ->count();
    }

    private function handoffRequestCount(User $user): int
    {
        return $this->visibleHandoffRequestQuery($user)
            ->where('status', HandoffRequest::STATUS_PENDING)
            ->count();
    }

    private function breakRequestCount(User $user): int
    {
        return $this->visibleBreakRequestQuery($user)
            ->where('status', BreakWorkRequest::STATUS_PENDING)
            ->count();
    }

    private function taskTimeExtendRequestCount(User $user): int
    {
        return $this->visibleTaskTimeExtendRequestQuery($user)
            ->where('status', 'pending')
            ->count();
    }

    /**
     * -------------------------Helper Methods
     */

    private function visibleTaskRequestQuery(User $user): Builder
    {
        $query = Task::query()->where('request_type', 'self');

        $accessibleUserIds = app(UserService::class)->getRequestAccessibleUsers($user);

        return $query->where(function (Builder $query) use ($accessibleUserIds, $user) {
            $query
                ->whereIn('current_assignee_id', $accessibleUserIds)
                ->orWhere(function (Builder $accountableQuery) use ($user) {
                    $accountableQuery->accountableBy($user);
                });
        });
    }

    private function visibleTaskTimeChangeRequestQuery(User $user): Builder
    {
        return app(TaskTimeLogChangeRequestService::class)->visibleRequestQuery($user);
    }

    private function visibleHandoffRequestQuery(User $user): Builder
    {
        $query = HandoffRequest::query();

        if ($user->is_super_admin) {
            return $query;
        }

        if ($user->can('handoff_request.view_all')) {
            return $query->whereHas('project', function (Builder $projectQuery) use ($user) {
                $projectQuery->accessibleBy($user);
            });
        }

        $accessibleUserIds = User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->push($user->id)
            ->unique()
            ->values()
            ->all();

        return $query->whereIn('user_id', $accessibleUserIds);
    }

    private function visibleBreakRequestQuery(User $user): Builder
    {
        if ($user->is_super_admin) {
            return BreakWorkRequest::query();
        }

        $accessibleUserIds = app(UserService::class)->getRequestAccessibleUsers($user);

        return BreakWorkRequest::query()->whereIn('user_id', $accessibleUserIds);
    }

    private function visibleTaskTimeExtendRequestQuery(User $user): Builder
    {
        return app(TaskTimeExtendService::class)->visibleRequestQuery($user);
    }

    private function empty(): array
    {
        return [
            'task_requests' => 0,
            'task_time' => 0,
            'task_handoff' => 0,
            'break_requests' => 0,
            'task_time_extend_requests' => 0,
            'has_any_pending' => false,
        ];
    }
}

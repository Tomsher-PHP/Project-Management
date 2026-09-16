<?php

namespace App\Services\Layout;

use App\Models\BreakWorkRequest;
use App\Models\HandoffRequest;
use App\Models\LeaveRequest;
use App\Models\Task;
use App\Models\User;
use App\Services\HandoffServices;
use App\Services\TaskServices;
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

        $leaveRequests = $this->leaveRequestCount($user);


        return [
            'task_requests' => $taskRequests,
            'task_time' => $taskTime,
            'task_handoff' => $taskHandoff,
            'break_requests' => $breakRequests,
            'task_time_extend_requests' => $taskTimeExtendRequests,
            'leave_requests' => $leaveRequests,
            'has_any_pending' => (
                $taskRequests
                + $taskTime
                + $taskHandoff
                + $breakRequests
                + $taskTimeExtendRequests
                + $leaveRequests
            ) > 0,
        ];
    }

    /**
     * ----------------------- Count methods
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

    private function leaveRequestCount(User $user): int
    {
        $query = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_PENDING);

        if ($user->is_super_admin) {
            return $query->count();
        }

        return $query
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereJsonContains('assigned_to', $user->id)
                    ->orWhereJsonContains('assigned_to', (string) $user->id);
            })
            ->count();
    }

    /**
     * ------------------------- Helper Methods
     */

    private function visibleTaskRequestQuery(User $user): Builder
    {
        return app(TaskServices::class)->visibleTaskRequestQuery($user);
    }

    private function visibleTaskTimeChangeRequestQuery(User $user): Builder
    {
        return app(TaskTimeLogChangeRequestService::class)->visibleRequestQuery($user);
    }

    private function visibleHandoffRequestQuery(User $user): Builder
    {
        return app(HandoffServices::class)->visibleRequestQuery($user);
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
            'leave_requests' => 0,
            'has_any_pending' => false,
        ];
    }
}

<?php

namespace App\Services\Layout;

use App\Models\BreakWorkRequest;
use App\Models\HandoffRequest;
use App\Models\Task;
use App\Models\TaskExtendTimeRequest;
use App\Models\TaskTimeLogChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RequestMenuBadgeService
{
    public function getPendingCountsForUser(?User $user): array
    {
        if (! $user) {
            return $this->empty();
        }

        $taskRequests = $user->canAny(['task.view', 'task.view_all_tasks'])
            ? $this->taskRequestCount($user)
            : 0;

        $taskTime = $user->can('task_time_log_change_request.approve_reject')
            ? $this->taskTimeChangeRequestCount($user)
            : 0;

        $taskHandoff = $user->canAny(['handoff_request.view', 'handoff_request.view_all'])
            ? $this->handoffRequestCount($user)
            : 0;

        $breakRequests = $this->breakRequestCount($user);

        $taskTimeExtendRequests = $user->can('task_time_extend_request.approve_reject')
            ? $this->taskTimeExtendRequestCount($user)
            : 0;

        return [
            'task_requests' => $taskRequests,
            'task_time' => $taskTime,
            'task_handoff' => $taskHandoff,
            'break_requests' => $breakRequests,
            'task_time_extend_requests' => $taskTimeExtendRequests,
            'has_any_pending' => ($taskRequests + $taskTime + $taskHandoff + $breakRequests) > 0,
        ];
    }

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

    private function visibleTaskRequestQuery(User $user): Builder
    {
        $query = Task::query()->where('request_type', 'self');

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

    private function visibleTaskTimeChangeRequestQuery(User $user): Builder
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

        $accessibleUserIds = User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->push($user->id)
            ->unique()
            ->values()
            ->all();

        return BreakWorkRequest::query()
            ->whereIn('user_id', $accessibleUserIds);
    }

    private function visibleTaskTimeExtendRequestQuery(User $user): Builder
    {
        if ($user->is_super_admin) {
            return TaskExtendTimeRequest::query();
        }

        $accessibleUserIds = User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->push($user->id)
            ->unique()
            ->values()
            ->all();

        return TaskExtendTimeRequest::query()
            ->whereIn('user_id', $accessibleUserIds);
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

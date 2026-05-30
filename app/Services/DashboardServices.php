<?php

namespace App\Services;

use App\Models\BreakWorkRequest;
use App\Models\HandoffRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTimeLogChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardServices
{
    /**
     * Get summary counts for dashboard
     *
     * @param User $user
     * @return array
     */
    public function getDashboardSummary(User $user): array
    {
        // Grouped Project Counts by type
        $projectCounts = Project::accessibleBy($user)
            ->join('project_statuses', 'projects.status_id', '=', 'project_statuses.id')
            ->whereNull('project_statuses.deleted_at')
            ->selectRaw('project_statuses.type, count(projects.id) as count')
            ->groupBy('project_statuses.type')
            ->pluck('count', 'type')
            ->toArray();

        $totalProjects = Project::accessibleBy($user)->count();

        // Grouped Task Counts by type
        $taskCounts = Task::accessibleBy($user)
            ->join('task_statuses', 'tasks.status_id', '=', 'task_statuses.id')
            ->whereNull('task_statuses.deleted_at')
            ->selectRaw('task_statuses.type, count(tasks.id) as count')
            ->groupBy('task_statuses.type')
            ->pluck('count', 'type')
            ->toArray();

        $totalTasks = Task::accessibleBy($user)->count();

        return [
            // Project counts
            'total_projects' => $totalProjects,
            'open_projects' => $projectCounts['open'] ?? 0,
            'in_progress_projects' => $projectCounts['in_progress'] ?? 0,
            'archived_projects' => $projectCounts['archived'] ?? 0,
            'completed_projects' => $projectCounts['completed'] ?? 0,

            // Task counts
            'total_tasks' => $totalTasks,
            'pending_tasks' => $taskCounts['pending'] ?? 0,
            'active_tasks' => $taskCounts['active'] ?? 0,
            'archived_tasks' => $taskCounts['archived'] ?? 0,
            'completed_tasks' => $taskCounts['completed'] ?? 0,
        ];
    }

    /**
     * Get request notification counts
     *
     * @param User $user
     * @return array
     */
    public function getRequestNotificationCounts(User $user): array
    {
        $taskRequests = 0;
        if ($user->canAny(['task.view', 'task.view_all_tasks'])) {
            $taskRequests = $this->visibleTaskRequestQuery($user)
                ->where('request_status', 'pending')
                ->count();
        }

        $taskTime = 0;
        if ($user->can('task_time_log_change_request.approve_reject')) {
            $taskTime = $this->visibleTaskTimeChangeRequestQuery($user)
                ->where('status', 'pending')
                ->count();
        }

        $taskHandoff = 0;
        if ($user->canAny(['handoff_request.view', 'handoff_request.view_all'])) {
            $taskHandoff = $this->visibleHandoffRequestQuery($user)
                ->where('status', 0) // HandoffRequest::STATUS_PENDING is 0
                ->count();
        }

        $breakRequests = $this->visibleBreakRequestQuery($user)
            ->where('status', 'pending')
            ->count();

        $totalRequestCount = $taskRequests + $taskTime + $taskHandoff + $breakRequests;

        return [
            'task_request_count' => $taskRequests,
            'task_log_time_request_count' => $taskTime,
            'handoff_request_count' => $taskHandoff,
            'break_request_count' => $breakRequests,
            'total_request_count' => $totalRequestCount,
        ];
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
}

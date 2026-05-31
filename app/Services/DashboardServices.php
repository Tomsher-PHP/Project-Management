<?php

namespace App\Services;

use App\Models\BreakWorkRequest;
use App\Models\HandoffRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\TaskTimeLogChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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

    /**
     * Get user worked time for a selected date
     *
     * @param User $user
     * @param string $date
     * @return array
     */
    public function getUsersTaskWorkedTime(User $user, string $date): array
    {
        $accessibleUserIds = User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->push($user->id)
            ->unique()
            ->values()
            ->all();

        $dateFormat = config('constants.date_format', 'Y-m-d');
        $formattedDate = Carbon::parse($date)->format($dateFormat);
        
        $timezone = config('constants.timezone', 'UTC');
        $selectedDate = Carbon::parse($date, $timezone)->timezone($timezone);
        $dayStartLocal = $selectedDate->copy()->startOfDay();
        $dayEndExclusiveLocal = $dayStartLocal->copy()->addDay();

        $dayStartUtc = $dayStartLocal->copy()->timezone('UTC');
        $dayEndExclusiveUtc = $dayEndExclusiveLocal->copy()->timezone('UTC');

        $logs = TaskTimeLog::query()
            ->whereIn('user_id', $accessibleUserIds)
            ->where('started_at', '<', $dayEndExclusiveUtc)
            ->where(function ($query) use ($dayStartUtc) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', $dayStartUtc);
            })
            ->with(['user' => function ($q) {
                $q->select('id', 'name')->with(['activeShift.weekends', 'primaryAttachment']);
            }])
            ->get();

        $userTotalSeconds = [];
        $userEarliestStart = [];
        $userLatestEnd = [];
        $userHasRunning = [];
        $usersMap = [];

        foreach ($logs as $log) {
            if (!$log->user) {
                continue;
            }

            $userId = $log->user_id;
            $usersMap[$userId] = $log->user;

            $startedAtLocal = $log->started_at->copy()->timezone($timezone);
            $endedAtLocal = $log->ended_at ? $log->ended_at->copy()->timezone($timezone) : null;

            $segmentStartLocal = $startedAtLocal->greaterThan($dayStartLocal)
                ? $startedAtLocal
                : $dayStartLocal->copy();

            $compareEndLocal = $endedAtLocal ?? Carbon::now($timezone);
            $segmentEndLocal = $compareEndLocal->lessThan($dayEndExclusiveLocal)
                ? $compareEndLocal
                : $dayEndExclusiveLocal->copy();

            if ($segmentEndLocal->greaterThan($segmentStartLocal)) {
                if (!$log->is_running) {
                    $overlapSeconds = $segmentStartLocal->diffInSeconds($segmentEndLocal);
                    if ($overlapSeconds > 0) {
                        $userTotalSeconds[$userId] = ($userTotalSeconds[$userId] ?? 0) + $overlapSeconds;
                    }
                }

                if (!isset($userEarliestStart[$userId]) || $startedAtLocal->lessThan($userEarliestStart[$userId])) {
                    $userEarliestStart[$userId] = $startedAtLocal;
                }

                if ($log->is_running) {
                    $userHasRunning[$userId] = true;
                } else {
                    if ($endedAtLocal) {
                        if (!isset($userLatestEnd[$userId]) || $endedAtLocal->greaterThan($userLatestEnd[$userId])) {
                            $userLatestEnd[$userId] = $endedAtLocal;
                        }
                    }
                }
            }
        }

        $result = [];
        foreach ($usersMap as $userId => $userObj) {
            $totalSeconds = $userTotalSeconds[$userId] ?? 0;
            
            $timeFormat = config('constants.time_format', 'H:i');
            $startStr = isset($userEarliestStart[$userId])
                ? $userEarliestStart[$userId]->format($timeFormat)
                : '--';

            if (!empty($userHasRunning[$userId])) {
                $endStr = 'Running';
            } else {
                $endStr = isset($userLatestEnd[$userId])
                    ? $userLatestEnd[$userId]->format($timeFormat)
                    : '--';
            }

            $activeShift = $userObj->activeShift ?? null;
            $shiftWorkingHour = '--';

            if ($activeShift) {
                $weekNumber = (int) ceil($selectedDate->day / 7);
                $isWeekend = $activeShift->weekends
                    ->where('weekday', $selectedDate->dayOfWeek)
                    ->where('week_number', $weekNumber)
                    ->isNotEmpty();

                if ($isWeekend) {
                    $shiftWorkingHour = 'Day Off';
                } else {
                    $start = Carbon::parse($activeShift->time_from);
                    $end = Carbon::parse($activeShift->time_to);

                    if ($end->lessThan($start)) {
                        $end->addDay();
                    }

                    $totalShiftSeconds = $start->diffInSeconds($end);
                    $workingSeconds = $totalShiftSeconds - ($activeShift->break_duration ?? 0);
                    $workingSeconds = max(0, $workingSeconds);
                    $shiftWorkingHour = formatSecondsToHMS($workingSeconds);
                }
            }

            $result[] = [
                'user_id' => $userId,
                'user' => $userObj,
                'user_name' => $userObj->name ?? 'Unknown',
                'user_avatar_html' => view('components.user-avatar', ['user' => $userObj, 'size' => 'sm'])->render(),
                'date' => $formattedDate,
                'start_time' => $startStr,
                'end_time' => $endStr,
                'shift_working_hour' => $shiftWorkingHour,
                'total_worked_time' => formatSecondsToHMS($totalSeconds),
            ];
        }

        return $result;
    }

    /**
     * Get active running tasks
     *
     * @param User $user
     * @return array
     */
    public function getRunningTasks(User $user): array
    {
        $accessibleUserIds = User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->push($user->id)
            ->unique()
            ->values()
            ->all();

        return TaskTimeLog::query()
            ->whereIn('user_id', $accessibleUserIds)
            ->where('is_running', true)
            ->with(['user' => function ($q) {
                $q->select('id', 'name')->with('primaryAttachment');
            }, 'task:id,name,estimated_time_seconds,actual_time_seconds'])
            ->get()
            ->map(function ($log) {
                $startedAt = $log->started_at;
                $elapsedSeconds = $startedAt ? $startedAt->diffInSeconds(now()) : 0;

                $task = $log->task;
                $estimatedSeconds = $task ? (int) $task->estimated_time_seconds : 0;
                $actualSeconds = $task ? (int) $task->actual_time_seconds : 0;

                $totalWorkedSeconds = $actualSeconds + $elapsedSeconds;

                $workedTimeFormatted = formatSecondsToHMS($totalWorkedSeconds);
                $estimatedTimeFormatted = $task && $estimatedSeconds > 0
                    ? formatSecondsToHMS($estimatedSeconds)
                    : '--';

                $isOverdue = $estimatedSeconds > 0 && $totalWorkedSeconds > $estimatedSeconds;
                $colorClass = $isOverdue
                    ? 'text-red-500 dark:text-red-400 font-bold'
                    : 'text-success-300 dark:text-success-400 font-bold';

                return [
                    'user' => $log->user,
                    'user_name' => $log->user->name ?? 'Unknown',
                    'user_avatar_html' => view('components.user-avatar', ['user' => $log->user, 'size' => 'sm'])->render(),
                    'task_name' => $task->name ?? 'Unnamed Task',
                    'task_id' => $log->task_id,
                    'estimated_time' => $estimatedTimeFormatted,
                    'worked_time' => $workedTimeFormatted,
                    'color_class' => $colorClass,
                ];
            })
            ->toArray();
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

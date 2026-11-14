<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskDeveloperNote;
use App\Models\TaskStatus;
use App\Models\TaskStatusHistory;
use App\Models\TaskTimeLog;
use App\Models\Team;
use App\Models\User;
use App\Models\UserShiftAssignment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyWorkReportService
{
    /**
     * Function to generate Individual Employee Report
     *
     * @var User $employee
     * @var CarbonInterface $start
     * @var CarbonInterface $end
     * @return array
     */
    public function generate(User $employee, CarbonInterface $start, CarbonInterface $end): array {
        $userId = $employee->id;
        $reportDate = $start->toDateString();

        /* Employee Roles */
        $roleNames = $employee->getRoleNames()->implode(', ');

        /* Employee Assigned Shift */
        $shiftAssignment = UserShiftAssignment::query()
            ->with('shift')
            ->where('user_id', $userId)
            ->whereDate('date_from', '<=', $reportDate)
            ->where(function ($query) use ($reportDate) {
                $query->whereNull('date_to')
                    ->orWhereDate('date_to', '>=', $reportDate);
            })
            ->orderByDesc('date_from')
            ->first();

        $assignedShift = null;

        if ($shiftAssignment) {
            $assignedShift = [
                'name' => $shiftAssignment->shift_name
                    ?? $shiftAssignment->shift?->name,
                'start' => $shiftAssignment->time_from,
                'end' => $shiftAssignment->time_to,
            ];
        }

        /* Currently Assigned Tasks */
        $currentTasks = Task::query()
            ->where('current_assignee_id', $userId)
            ->with([
                'project',
                'status',
                'developerNotes',
            ])
            ->whereHas('project', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        /* Time Logs During Reporting Period */
        $timeLogs = TaskTimeLog::query()
            ->with(['task.project'])
            ->where('user_id', $userId)
            ->whereBetween('started_at', [$start, $end])
            ->whereHas('task', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        /* Tasks With Time Logged */
        $loggedTaskIds = $timeLogs
            ->pluck('task_id')
            ->filter()
            ->unique()
            ->values();

        $loggedTasks = Task::query()
            ->whereIn('id', $loggedTaskIds)
            ->with([
                'project',
                'status',
                'developerNotes',
            ])
            ->whereHas('project', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        /* Workload Counts */
        // Total Tasks: all currently assigned tasks due today or earlier,
        // regardless of their current status (including completed tasks).
        $totalDueTasks = $currentTasks->filter(
            function ($task) use ($reportDate) {
                $dueDate = $task->due_date_time;

                return $dueDate
                    && $dueDate->toDateString() <= $reportDate
                    && ! $task->status?->is_completed;
            }
        );

        // Pending: tasks still in To Do/Pending status and due today or earlier.
        $pendingTasks = $currentTasks->filter(
            function ($task) use ($reportDate) {
                $dueDate = $task->due_date_time;

                return $task->status?->type === TaskStatus::TYPE_PENDING
                    && $dueDate
                    && $dueDate->toDateString() <= $reportDate;
            }
        );

        /* In Progress Tasks */
        $inProgressTasks = $currentTasks->filter(
            function ($task) use ($loggedTaskIds) {
                return $task->status?->type === TaskStatus::TYPE_ACTIVE
                    && $loggedTaskIds->contains($task->id);
            }
        );

        /* Overdue Tasks */
        $overdueTasks = $currentTasks->filter(
            function ($task) use ($reportDate) {
                $dueDate = $task->due_date_time;

                return $dueDate
                    && $dueDate->toDateString() < $reportDate
                    && ! $task->status?->is_completed;
            }
        );

        /* Task Status Changes During Reporting Period */
        $statusChanges = TaskStatusHistory::query()
            ->with([
                'task.project',
                'status',
            ])
            ->where('added_by', $userId)
            ->whereBetween('added_at', [$start, $end])
            ->whereHas('task', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        /* Employee's Completed Task Activity */
        $completedTaskIds = $statusChanges
            ->filter(
                fn ($history) => $history->status?->is_completed
            )
            ->pluck('task_id')
            ->unique()
            ->values();

        /* Completed Tasks With Time Logged */
        $completedLoggedTaskIds = TaskStatusHistory::query()
            ->whereIn('task_id', $loggedTaskIds)
            ->whereBetween('added_at', [$start, $end])
            ->whereHas('status', function ($query) {
                $query->where('is_completed', true);
            })
            ->whereHas('task', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->pluck('task_id')
            ->unique()
            ->values();

        /* Comments During Reporting Period */
        $comments = TaskComment::query()
            ->with(['task.project'])
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        /* Developer Notes During Reporting Period */
        $developerNotes = TaskDeveloperNote::query()
            ->with(['task.project'])
            ->where('created_by', $userId)
            ->whereBetween('updated_at', [$start, $end])
            ->get();

        /* New Task Assignments */
        $newAssignments = $employee->taskAssignmentLogs()
            ->with(['task.project'])
            ->whereBetween('assigned_to', [$start, $end])
            ->get();

        $totalLoggedSeconds = (int) $timeLogs->sum(
            fn ($log) => (int) ($log->duration_seconds ?? 0)
        );

        $upcomingTasks = $currentTasks->filter(
            function ($task) use ($reportDate) {
                $dueDate = $task->due_date_time;

                return $task->status?->type === 'pending'
                    && (
                        !$dueDate
                        || $dueDate->toDateString() > $reportDate
                    );
            }
        );

        /* Report Data */
        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $roleNames ?: 'No Role Assigned',
                'roles' => $roleNames ?: 'No Role Assigned',
                'teams' => [],
            ],

            'period' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ],

            'task_count_urls' => $this->taskCountUrls(
                $employee,
                $start,
                $end
            ),

            'shift' => $assignedShift,

            /* Workload Overview */

            'current_workload' => [
                'total' => $totalDueTasks->count(),
                'completed' => $completedLoggedTaskIds->count(),
                'in_progress' => $inProgressTasks->count(),
                'pending' => $pendingTasks->count(),
                'overdue' => $overdueTasks->count(),
                'tasks' => $loggedTasks->map(
                    fn ($task) => $this->taskSummary($task)
                )->values()->all(),
                'pending_tasks' => $pendingTasks->map(
                    fn ($task) => $this->taskSummary($task)
                )->values()->all(),

                'upcoming' => $upcomingTasks->count(),
                'upcoming_tasks' => $upcomingTasks->map(
                    fn ($task) => $this->taskSummary($task)
                )->values()->all(),
            ],

            /* Daily Activity */

            'daily_activity' => [
                'completed_task_ids' => $completedTaskIds->all(),

                'status_changes' => $statusChanges->map(
                    fn ($history) => [
                        'task_id' => $history->task_id,
                        'task_name' => $history->task?->name,
                        'project_name' => $history->task?->project?->name,
                        'status' => $history->status?->name,
                        'changed_at' => $history->added_at?->toIso8601String(),
                        'remarks' => $history->remarks,
                    ]
                )->values()->all(),

                'new_assignments' => $newAssignments->map(
                    fn ($log) => [
                        'task_id' => $log->task?->id,
                        'task_name' => $log->task?->name,
                        'project_name' => $log->task?->project?->name,
                        'assigned_at' => $log->assigned_to,
                    ]
                )->values()->all(),
            ],

            /* Time Logs */
            'time_logs' => [
                'total_seconds' => $totalLoggedSeconds,

                'entries' => $timeLogs->map(function ($log) {
                    return [
                        'task_name' => $log->task?->name,
                        'project_name' => $log->task?->project?->name,
                        'started_at' => $log->started_at,
                        'ended_at' => $log->ended_at,
                        'duration_seconds' => (int) ($log->duration_seconds ?? 0),
                        'note' => $log->note,
                    ];
                })->values()->all(),
            ],

            /* Comments */
            'comments' => $comments->map(
                fn ($comment) => [
                    'task_name' => $comment->task?->name,
                    'project_name' => $comment->task?->project?->name,
                    'comment' => $comment->comment,
                    'created_at' => $comment->created_at?->toIso8601String(),
                ]
            )->values()->all(),

            /* Developer Notes */
            'developer_notes' => $developerNotes->map(
                fn ($note) => [
                    'task_name' => $note->task?->name,
                    'project_name' => $note->task?->project?->name,
                    'note' => $note->note,
                    'updated_at' => $note->updated_at?->toIso8601String(),
                ]
            )->values()->all(),
        ];
    }

    /**
     *
     * Task Summary
     *
     * @var Task $task
     * @return array
     */
    private function taskSummary(Task $task): array
    {
        $dueDate = $task->due_date_time;
        $completedAt = $task->completed_at;

        $completionPerformance = null;
        $completionDifferenceSeconds = null;

        if (
            $task->status?->is_completed
            && $dueDate
            && $completedAt
        ) {
            $completionDifferenceSeconds = $dueDate->getTimestamp()
                - $completedAt->getTimestamp();

            if ($completionDifferenceSeconds > 0) {
                $completionPerformance = 'early';
            } elseif ($completionDifferenceSeconds === 0) {
                $completionPerformance = 'on_time';
            } else {
                $completionPerformance = 'late';
            }
        }

        $latestDeveloperNote = $task->developerNotes
            ->sortByDesc('updated_at')
            ->first();

        return [
            'id' => $task->id,
            'name' => $task->name,
            'code' => $task->code,
            // Links
            'url' => route('tasks.edit', $task->id),
            'project_url' => $task->project_id
                ? route('projects.edit', $task->project_id)
                : null,
            'project' => $task->project?->name,
            'milestone' => $task->projectMilestone?->name,
            'sprints' => $task->projectSprint?->name ?? '-',
            'status' => $task->status?->name,
            'due_date' => $dueDate?->toIso8601String(),
            'completed_at' => $completedAt?->toIso8601String(),
            'completion_performance' => $completionPerformance,
            'completion_difference_seconds' => $completionDifferenceSeconds,
            'estimated_time_seconds' => (int) $task->estimated_time_seconds,
            'actual_time_seconds' => (int) $task->actual_time_seconds,
            'developer_note' => $latestDeveloperNote?->note,
        ];
    }

    /**
     *
     * Consolidated Team-wise Report
     * The employees collection must be supplied by the existing recipient
     * reporting-hierarchy logic.
     * This method does not expand the reporting scope by adding every
     * user belonging to the same team.
     */
    public function generateConsolidated(User $reportOwner, Collection $employees, CarbonInterface $start, CarbonInterface $end): array {

        /* Normalize Employees */
        $employees = $employees
            ->filter(fn ($employee) => $employee instanceof User)
            ->reject(fn ($employee) => $employee->id == $reportOwner->id)
            ->unique('id')
            ->values();

        /* Generate Individual Reports */
        $employeeReportsById = $employees->mapWithKeys(
            function (User $employee) use ($start, $end) {

                $report = $this->generate($employee, $start, $end);

                return [$employee->id => $report];
            }
        );

        /**
         * Retrieve Team Details
         *
         * Load all users belonging to teams that contain at least one
         * employee in this report. This lets us identify the team leader
         * even if the leader is outside the current reporting scope.
         */
        $employeeIds = $employees->pluck('id')->all();
        $teams = collect();

        if (!empty($employeeIds)) {
            $teams = Team::query()
                ->whereHas('users', function ($query) use ($employeeIds) {
                    $query->whereIn('users.id', $employeeIds);
                })
                ->with([
                    'users' => function ($query) {
                        $query->select(
                            'users.id',
                            'users.name',
                            'users.email'
                        );
                    },
                ])
                ->get();
        }

        /* Build User Team Details */

        $teamDetailsByUser = [];

        foreach ($teams as $team) {
            foreach ($team->users as $teamUser) {

                $teamRole = $teamUser->pivot->team_role ?? null;

                $teamDetailsByUser[$teamUser->id][] = [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'team_role' => $teamRole,
                    'position' => $teamRole === 'team_leader'
                        ? 'Team Lead'
                        : 'Team Member',
                ];
            }
        }

        /* Attach Team Details to Each Employee Report */

        foreach ($employeeReportsById as $employeeId => $report) {
            $report['employee']['teams'] =
                $teamDetailsByUser[$employeeId] ?? [];
            $employeeReportsById->put($employeeId, $report);
        }

        /* Build Team-wise Report Groups */

        $teamGroups = [];
        $assignedEmployeeIds = [];

        foreach ($teams as $team) {
            $leader = null;
            $members = [];
            $teamReportCount = 0;

            foreach ($team->users as $teamUser) {
                $teamRole = $teamUser->pivot->team_role ?? null;

                /* Team Leader */

                if ($teamRole === 'team_leader') {
                    $leaderReport = $employeeReportsById->get($teamUser->id);
                    $leader = [
                        'id' => $teamUser->id,
                        'name' => $teamUser->name,
                        'email' => $teamUser->email,
                        'role' => $leaderReport
                            ? data_get($leaderReport, 'employee.role')
                            : $teamUser->getRoleNames()->implode(', '),
                        'position' => 'Team Lead',

                        // Null when the leader is not in this report's scope.
                        'report' => $leaderReport,
                    ];

                    if ($leaderReport !== null) {
                        $assignedEmployeeIds[] = $teamUser->id;
                        $teamReportCount++;
                    }

                    continue;
                }

                /* Team Members */

                $memberReport = $employeeReportsById->get($teamUser->id);

                if ($memberReport === null) {
                    continue;
                }

                $memberReport['employee']['team_position'] = 'Team Member';
                $members[] = $memberReport;
                $assignedEmployeeIds[] = $teamUser->id;
                $teamReportCount++;
            }

            // Skip Teams Without Any Reports

            if ($teamReportCount === 0) {
                continue;
            }

            $teamGroups[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'leader' => $leader,
                'members' => $members,
                'employee_count' => $teamReportCount,
            ];
        }

        //Employees Without a Team
        $assignedEmployeeIds = array_unique($assignedEmployeeIds);
        $noTeamEmployees = $employeeReportsById
            ->reject(function ($report, $employeeId) use ($assignedEmployeeIds) {
                return in_array($employeeId, $assignedEmployeeIds);
            })
            ->map(function ($report) {
                $report['employee']['team_position'] = 'No Team Assigned';

                return $report;
            })
            ->values()
            ->all();

        if (!empty($noTeamEmployees)) {
            $teamGroups[] = [
                'team_id' => null,
                'team_name' => 'No Team Assigned',
                'leader' => null,
                'members' => $noTeamEmployees,
                'employee_count' => count($noTeamEmployees),
            ];
        }

        // Consolidated Report

        return [
            'report_owner' => [
                'id' => $reportOwner->id,
                'name' => $reportOwner->name,
                'email' => $reportOwner->email,
                'roles' => $reportOwner->getRoleNames()->implode(', '),
            ],

            'period' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ],

            'employee_count' => $employeeReportsById->count(),

            // Preserve the original flat reports for compatibility.
            'employees' => $employeeReportsById->values()->all(),

            // Use this structure for the team-wise email and preview.
            'team_groups' => $teamGroups,
        ];
    }

    /**
     * Get the task count URLs for the given employee and date range.
     *
     * @var User  $employee
     * @var  CarbonInterface  $start
     * @var  CarbonInterface  $end
     * @return array
     */
    private function taskCountUrls(User $employee, CarbonInterface $start, CarbonInterface $end): array {
        $userId = $employee->id;
        $reportDate = $start->toDateString();

        $pendingStatusIds = TaskStatus::query()
            ->where('type', TaskStatus::TYPE_PENDING)
            ->pluck('id')
            ->all();

        $activeStatusIds = TaskStatus::query()
            ->where('type', TaskStatus::TYPE_ACTIVE)
            ->pluck('id')
            ->all();

        $buildUrl = function (
            array $filters,
            bool $filterAssignee = true
        ) use ($userId) {
            $employeeFilters = $filterAssignee
                ? [
                    'current_assignee_id' => [$userId],
                    'user_filter_applied' => 1,
                ]
                : [];

            return route('reports.tasks', array_merge(
                $employeeFilters,
                $filters
            ));
        };

        return [
            'total' => $buildUrl([
                'due_date_end' => $reportDate,
                'exclude_completed' => 1,
            ]),

            'pending' => $buildUrl([
                'status_id' => $pendingStatusIds,
                'due_date_end' => $reportDate,
            ]),

            'in_progress' => $buildUrl([
                'status_id' => $activeStatusIds,
                'worked_on' => $reportDate,
                'worked_by' => $userId,
            ]),

            'completed' => $buildUrl([
                'completed_on' => $reportDate,
                'worked_on' => $reportDate,
                'worked_by' => $userId,
            ], false),

            'upcoming' => $buildUrl([
                'status_id' => $pendingStatusIds,
                'due_date_upcoming' => 1,
                'reference_date' => $reportDate,
            ]),

            'overdue' => $buildUrl([
                'due_date_end' => \Carbon\Carbon::parse($reportDate)
                    ->subDay()
                    ->toDateString(),
                'exclude_completed' => 1,
            ]),
        ];
    }
}

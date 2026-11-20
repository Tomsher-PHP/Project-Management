<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskDeveloperNote;
use App\Models\TaskStatus;
use App\Models\TaskStatusHistory;
use App\Models\TaskTimeLog;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyWorkReportService
{
    /**
     * Generate an individual employee report.
     *
     * @var User $employee
     * @var CarbonInterface $start
     * @var CarbonInterface $end
     * @return array
     */
    public function generate(User $employee, CarbonInterface $start, CarbonInterface $end): array
    {
        $userId = $employee->id;
        $reportDate = $end->toDateString();

        /* Employee Roles */
        $roleNames = $employee->getRoleNames()->implode(', ');

        /* Employee Assigned Shift */
        $timelineShift = app(UserTimelineService::class)->getAssignedShift(
            $userId,
            $reportDate
        );

        $assignedShift = $timelineShift ? [
            'name' => $timelineShift['shift_name'] ?? null,
            'start' => $timelineShift['time_from'] ?? null,
            'end' => $timelineShift['time_to'] ?? null,
            'is_weekend' => (bool) ($timelineShift['is_weekend'] ?? false),
        ] : null;

        /* Employee Attendance */
        $workspaceHoliday = Holiday::forUserOnDate($employee, $reportDate);

        $workspaceLeave = LeaveRequest::query()
            ->with('leaveType:id,name')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereRaw(
                'DATE(COALESCE(approved_from_date, requested_from_date)) <= ?',
                [$reportDate]
            )
            ->whereRaw(
                'DATE(COALESCE(approved_to_date, requested_to_date)) >= ?',
                [$reportDate]
            )
            ->first();

        $leaveTypeName = trim($workspaceLeave?->leaveType?->name ?? '');
        $attendanceStatus = null;
        $attendanceLabel = null;

        if ($workspaceHoliday) {
            $attendanceStatus = 'holiday';
            $attendanceLabel = $workspaceHoliday->name;
        } elseif ($leaveTypeName !== '') {
            $attendanceStatus = 'leave';
            $attendanceLabel = 'On ' . $leaveTypeName;

            if (!preg_match('/leave$/i', $leaveTypeName)) {
                $attendanceLabel .= ' Leave';
            }
        } elseif ($assignedShift['is_weekend'] ?? false) {
            $attendanceStatus = 'day_off';
            $attendanceLabel = 'Day Off';
        }

        /* Exclude Tasks Whose Request Status Is Rejected */
        $excludeRejected = function ($query) {
            $query->where(function ($statusQuery) {
                $statusQuery->whereNull('request_status')
                    ->orWhereRaw('LOWER(request_status) != ?', ['rejected']);
            });
        };

        /* Currently Assigned Tasks */
        $currentTasks = Task::query()
            ->where('current_assignee_id', $userId)
            ->with([
                'project',
                'status',
                'developerNotes',
                'projectMilestone',
                'projectSprint',
            ])
            ->whereHas('project', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->where(function ($query) use ($excludeRejected) {
                $excludeRejected($query);
            })
            ->get();

        /* Time Logs During Reporting Period */
        $timezone = config('constants.timezone', 'UTC');

        $selectedDate = Carbon::parse($reportDate, $timezone);

        $dayStartLocal = $selectedDate->copy()->startOfDay();
        $dayEndLocal = $dayStartLocal->copy()->addDay();

        $dayStartUtc = $dayStartLocal->copy()->timezone('UTC');
        $dayEndUtc = $dayEndLocal->copy()->timezone('UTC');

        $timeLogs = TaskTimeLog::query()
            ->with(['task.project'])
            ->where('user_id', $userId)
            ->where('started_at', '<', $dayEndUtc)
            ->where(function ($query) use ($dayStartUtc) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', $dayStartUtc);
            })
            ->whereHas('task', function ($query) use ($excludeRejected) {
                $query->whereNull('deleted_at')
                    ->whereHas('project', function ($projectQuery) {
                        $projectQuery->whereNull('deleted_at');
                    });

                $excludeRejected($query);
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
                'projectMilestone',
                'projectSprint',
            ])
            ->whereHas('project', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->where(function ($query) use ($excludeRejected) {
                $excludeRejected($query);
            })
            ->get();

        /* Workload Counts */

        // Total: currently assigned, due today or earlier, and not completed.
        $totalDueTasks = $currentTasks->filter(
            function ($task) use ($reportDate) {
                $dueDate = $task->due_date_time;

                return $dueDate
                    && $dueDate->toDateString() <= $reportDate
                    && ! $task->status?->is_completed;
            }
        );

        // Pending: any non-completed status with a due date exactly on the report date.
        $pendingTasks = $currentTasks->filter(
            function ($task) use ($reportDate) {
                $dueDate = $task->due_date_time;

                return $dueDate
                    && $dueDate->toDateString() === $reportDate
                    && ! $task->status?->is_completed;
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
            ->whereHas('task', function ($query) use ($excludeRejected) {
                $query->whereNull('deleted_at')
                    ->whereHas('project', function ($projectQuery) {
                        $projectQuery->whereNull('deleted_at');
                    });

                $excludeRejected($query);
            })
            ->get();

        /* Employee's Completed Task Activity */
        $completedTaskIds = $statusChanges
            ->filter(fn ($history) => $history->status?->is_completed)
            ->pluck('task_id')
            ->unique()
            ->values();

        /* Comments During Reporting Period */
        $comments = TaskComment::query()
            ->with(['task.project'])
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('task', function ($query) use ($excludeRejected) {
                $query->whereNull('deleted_at')
                    ->whereHas('project', function ($projectQuery) {
                        $projectQuery->whereNull('deleted_at');
                    });

                $excludeRejected($query);
            })
            ->get();

        /* Developer Notes During Reporting Period */
        $developerNotes = TaskDeveloperNote::query()
            ->with(['task.project'])
            ->where('created_by', $userId)
            ->whereBetween('updated_at', [$start, $end])
            ->whereHas('task', function ($query) use ($excludeRejected) {
                $query->whereNull('deleted_at')
                    ->whereHas('project', function ($projectQuery) {
                        $projectQuery->whereNull('deleted_at');
                    });

                $excludeRejected($query);
            })
            ->get();

        /* New Task Assignments */
        $newAssignments = $employee->taskAssignmentLogs()
            ->with(['task.project'])
            ->whereBetween('assigned_to', [$start, $end])
            ->whereHas('task', function ($query) use ($excludeRejected) {
                $query->whereNull('deleted_at')
                    ->whereHas('project', function ($projectQuery) {
                        $projectQuery->whereNull('deleted_at');
                    });

                $excludeRejected($query);
            })
            ->get();

        /* Calculate Daily Worked Time */
        $totalLoggedSeconds = 0;
        $userEarliestStart = null;
        $userLatestEnd = null;
        $userHasRunning = false;
        $timeLogEntries = [];

        foreach ($timeLogs as $log) {
            if (!$log->started_at) {
                continue;
            }

            $startedAtLocal = $log->started_at->copy()->timezone($timezone);
            $endedAtLocal = $log->ended_at
                ? $log->ended_at->copy()->timezone($timezone)
                : null;

            $segmentStart = $startedAtLocal->greaterThan($dayStartLocal)
                ? $startedAtLocal
                : $dayStartLocal->copy();

            $rawEnd = $endedAtLocal ?? $dayEndLocal;

            $segmentEnd = $rawEnd->lessThan($dayEndLocal)
                ? $rawEnd
                : $dayEndLocal->copy();

            if (!$segmentEnd->greaterThan($segmentStart)) {
                continue;
            }

            $seconds = 0;

            if (!$log->is_running) {
                $seconds = (int) $segmentStart->diffInSeconds($segmentEnd);
                $totalLoggedSeconds += $seconds;
            }

            if (
                !$userEarliestStart ||
                $segmentStart->lessThan($userEarliestStart)
            ) {
                $userEarliestStart = $segmentStart->copy();
            }

            if ($log->is_running) {
                $userHasRunning = true;
            } elseif (
                !$userLatestEnd ||
                $segmentEnd->greaterThan($userLatestEnd)
            ) {
                $userLatestEnd = $segmentEnd->copy();
            }

            $timeLogEntries[] = [
                'task_name' => $log->task?->name,
                'project_name' => $log->task?->project?->name,
                'started_at' => $segmentStart->toIso8601String(),
                'ended_at' => $log->is_running
                    ? null
                    : $segmentEnd->toIso8601String(),
                'duration_seconds' => $seconds,
                'note' => $log->note,
            ];
        }

        $timeFormat = config('constants.time_format', 'H:i');

        $workedStartTime = $userEarliestStart
            ? $userEarliestStart->format($timeFormat)
            : '--';

        $workedEndTime = $userHasRunning
            ? 'Running'
            : ($userLatestEnd
                ? $userLatestEnd->format($timeFormat)
                : '--');

        /* Upcoming Tasks */
        $upcomingTasks = $currentTasks->filter(
            function ($task) use ($reportDate) {
                $dueDate = $task->due_date_time;

                return $task->status?->type === TaskStatus::TYPE_PENDING
                    && (
                        ! $dueDate
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

            'attendance' => [
                'status' => $attendanceLabel,
                'type' => $attendanceStatus,
                'label' => $attendanceLabel,
            ],

            /* Workload Overview */
            'current_workload' => [
                'total' => $totalDueTasks->count(),
                'completed' => $completedTaskIds->count(),
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
                'start_time' => $workedStartTime,
                'end_time' => $workedEndTime,
                'total_seconds' => $totalLoggedSeconds,
                'total_worked_time' => formatSecondsToHMS($totalLoggedSeconds),
                'entries' => $timeLogEntries,
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
     * Generate a task summary.
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
            'request_type' => $task->request_type,
            'description' => $task->description,
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
     * Generate a consolidated team-wise report.
     *
     * The employees collection must be supplied by the existing recipient
     * reporting-hierarchy logic. This method does not expand the reporting
     * scope by adding every user belonging to the same team.
     */
    public function generateConsolidated(
        User $reportOwner,
        Collection $employees,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
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
         * Retrieve team details.
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

            // Skip teams without any reports.
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

        /* Employees Without a Team */
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

        /* Consolidated Report */
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
     * Get task count URLs for the given employee and date range.
     *
     * @var User $employee
     * @var CarbonInterface $start
     * @var CarbonInterface $end
     * @return array
     */
    private function taskCountUrls(
        User $employee,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $userId = $employee->id;
        $reportDate = $end->toDateString();

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
                'due_date_start' => $reportDate,
                'due_date_end' => $reportDate,
                'exclude_completed' => 1,
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
                'due_date_end' => $end->copy()
                    ->subDay()
                    ->toDateString(),
                'exclude_completed' => 1,
            ]),
        ];
    }
}

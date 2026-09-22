@php
    $authUser = auth()->user();
    $isSuperAdmin = $authUser?->is_super_admin;
    $requestMenuBadges = $requestMenuBadges ?? [
        'task_requests' => 0,
        'task_time' => 0,
        'task_handoff' => 0,
        'break_requests' => 0,
        'task_time_extend_requests' => 0,
        'leave_requests' => 0,
        'has_any_pending' => false,
    ];

    $canViewDashboard = $authUser?->can('dashboard.view');
    $canViewRoles = $authUser?->can('role.view');
    $canViewUsers = $authUser?->canAny(['user.view', 'user.view_all_users']);
    $canViewTeams = $authUser?->canAny(['team.view', 'team.view_all_teams']);
    $canViewCustomers = $authUser?->can('customer.view');

    $canViewProjects = $authUser?->canAny(['project.view', 'project.view_all_projects']);
    $canViewTasks = $authUser?->canAny(['task.view', 'task.view_all_tasks']);
    $canViewTaskRequests = $authUser !== null;
    $canViewTaskTimeLogChangeRequests = $authUser !== null; //$authUser?->can('task_time_log_change_request.approve_reject');
    $canViewHandoffs = $authUser?->canAny(['handoff_request.view', 'handoff_request.view_all']);
    $canViewBreakRequests = $authUser !== null;
    $canViewTaskTimeExtendRequests = $authUser !== null; //$authUser?->can('task_time_extend_request.approve_reject');

    $canViewScheduleShift = $authUser?->can('schedule_shift.view');

    $settingsPermissions = config('constants.settings_permissions');
    // $canViewSettings = collect($settingsPermissions)->contains(fn($permission) => auth()->user()->can($permission));
    $canViewSettings = $authUser?->canAny($settingsPermissions) ?? false;

    $canViewActivityLog = $authUser?->can('activity_log.view');

    $canViewProjectReports = $authUser?->can('reports.project_view');
    $canViewTaskReports = $authUser?->can('reports.task_view');
    $canViewTimeTrackingReports = $authUser?->can('reports.time_tracking_view');
    $canViewAttendanceReports = $authUser?->can('reports.attendance_view');
    $canViewDailyReports = $authUser?->can('reports.daily_time_view');
    $canViewShiftScheduleReports = $authUser?->can('reports.shift_schedule_view');
    $canViewProductivityReports = $authUser?->can('reports.productivity_view');
    $canViewSprintReports = $authUser?->can('reports.sprint_view');
    $canViewMilestoneReports = $authUser?->can('reports.milestone_view');
    $canViewLeaveReports = $authUser?->can('reports.leave_view');

    $canViewAppraisal = $authUser?->can('appraisal.view');
    $canViewLeaveRequests = $authUser?->can('leave_request.view');
    $canViewAttendance = $authUser?->can('attendance.view');
    $canViewHolidays = $authUser?->can('holidays.view');
    $canViewMeetings = $authUser?->canAny(['meeting.view', 'meeting.view_all', 'meeting.create']);
    $canViewCompanyExpenses = $authUser?->canAny(['expense.view', 'expense.view_all']) ?? false;
    $canViewChequeExpenses = $authUser?->canAny(['check_expense.view', 'check_expense.view_all']) ?? false;
    $canViewReimbursements = $authUser?->canAny(['reimbursement.view', 'reimbursement.view_all']) ?? false;

    $canViewExpenses = $canViewCompanyExpenses || $canViewChequeExpenses || $canViewReimbursements;

    $expensesRoute = match (true) {
        $canViewCompanyExpenses => route('expenses.index'),
        $canViewChequeExpenses => route('cheques.index'),
        $canViewReimbursements => route('reimbursements.index'),
        default => route('expenses.index'),
    };

    $hasManagementLinks = $canViewUsers || $canViewTeams || $canViewCustomers;
    $hasWorkspaceLinks = $canViewProjects || $canViewTasks || $canViewMeetings || $canViewTaskRequests || $canViewTaskTimeLogChangeRequests || $canViewBreakRequests || $canViewLeaveRequests || $canViewAttendance || $canViewAppraisal || $canViewHolidays || $canViewExpenses;
    $hasConfigurationLinks = $canViewScheduleShift || $canViewSettings || $canViewActivityLog;
    $canViewReports = $canViewProductivityReports || $canViewTimeTrackingReports || $canViewDailyReports || $canViewAttendanceReports || $canViewLeaveReports || $canViewShiftScheduleReports || $canViewProjectReports || $canViewMilestoneReports || $canViewSprintReports || $canViewTaskReports;

    $isDashboardActive = request()->routeIs('dashboard');
    $isWorkspaceActive = request()->routeIs('user.workspace');
    $isAnalyticsActive = request()->routeIs('user.analytics');
    $isRolesActive = request()->routeIs('roles.*');
    $isUsersActive = request()->routeIs('users.*');
    $isTeamsActive = request()->routeIs('teams.*');
    $isCustomersActive = request()->routeIs('customers.*');
    $isProjectsActive = request()->routeIs('projects.*');
    $isMeetingsActive = request()->routeIs('meetings.*');
    $isExpensesActive = request()->routeIs('expenses.*', 'cheques.*', 'reimbursements.*');
    $isKanbanActive = request()->routeIs('tasks.kanban.view', 'tasks.kanbanMode');
    $isScheduleTasksActive = request()->routeIs('schedule-tasks.*');

    $isTaskRequestsActive = request()->routeIs('tasks.requests.*');
    $isTaskTimeChangeRequestsActive = request()->routeIs('tasks.time-log-change-requests.*');
    $isHandoffsActive = request()->routeIs('handoff_requests.*');
    $isBreakRequestsActive = request()->routeIs('break-requests.*');
    $isTaskTimeExtendRequestsActive = request()->routeIs('tasks.extend-time-requests.*');
    $isLeaveRequestsActive = request()->routeIs('leave-requests.*');
    $isLeaveApprovalRequestsActive = request()->routeIs('leave-requests.approval*');
    $isLeavesActive = request()->routeIs('leaves.*');

    $isRequestsMenuActive = $isTaskRequestsActive || $isTaskTimeChangeRequestsActive || $isHandoffsActive || $isBreakRequestsActive || $isTaskTimeExtendRequestsActive || $isLeaveApprovalRequestsActive;
    $isTasksActive = request()->routeIs('tasks.*') && !$isKanbanActive && !$isTaskRequestsActive && !$isTaskTimeChangeRequestsActive;

    $isProductivityReportActive = request()->routeIs('reports.productivity', 'reports.productivity.*');
    $isTimeTrackingReportActive = request()->routeIs('reports.time_tracking', 'reports.time_tracking.export');
    $isDailyReportActive = request()->routeIs('reports.daily_time', 'reports.daily_time.export');
    $isPerformanceReportsMenuActive = $isProductivityReportActive || $isTimeTrackingReportActive || $isDailyReportActive;

    $isAttendanceReportActive = request()->routeIs('reports.attendance', 'reports.attendance.*');
    $isLeaveReportActive = request()->routeIs('reports.leave', 'reports.leave.*');
    $isShiftScheduleReportActive = request()->routeIs('reports.shift.schedule', 'reports.shift.schedule.*');
    $isResourcesReportsMenuActive = $isAttendanceReportActive || $isLeaveReportActive || $isShiftScheduleReportActive;

    $isProjectReportActive = request()->routeIs('reports.projects', 'reports.project.export', 'reports.projects.by-flow');
    $isMilestoneReportActive = request()->routeIs('reports.milestones', 'reports.milestone.export');
    $isSprintReportActive = request()->routeIs('reports.sprints', 'reports.sprint.export');
    $isTaskReportActive = request()->routeIs('reports.tasks', 'reports.task.export');
    $isProjectsReportsMenuActive = $isProjectReportActive || $isMilestoneReportActive || $isSprintReportActive || $isTaskReportActive;

    $isScheduleShiftActive = request()->routeIs('schedule.shift.*');
    $isSettingsActive = request()->routeIs('settings.*');
    $isActivityLogActive = request()->routeIs('activity.log*');

    $isAppraisalActive = request()->routeIs('appraisal.*');

    $sidebarItemActiveClass = 'text-success-400 dark:text-success-300';
    $sidebarItemInactiveClass = 'text-bgray-900 dark:text-white';
    $sidebarSubLinkActiveClass = 'text-success-400 dark:text-success-300';
    $sidebarSubLinkInactiveClass = 'text-bgray-600 dark:text-bgray-50 hover:text-bgray-800 hover:dark:text-success-300';
@endphp

<aside class="relative hidden w-[96px] border-r border-bgray-200 bg-white dark:border-darkblack-500 dark:bg-black sm:block">
    <div class="sidebar-wrapper-collapse relative top-0 z-30 w-full">
        <div class="sidebar-header sticky top-0 z-20 flex h-[84px] w-full items-center justify-center border-b border-r border-bgray-200 bg-white dark:border-darkblack-500 dark:bg-darkblack-600">
            <a href="{{ $canViewDashboard ? route('dashboard') : route('user.workspace') }}">
                <img src="{{ asset(config('assets.icons.logo_short')) }}" class="block dark:hidden" alt="logo" />
                <img src="{{ asset(config('assets.icons.logo_short')) }}" class="hidden dark:block" alt="logo" />
            </a>
        </div>
        <div class="sidebar-body w-full pt-[14px]">
            <div class="flex flex-col items-center">
                <div class="nav-wrapper mb-[36px]">

                    <!-- MENU GROUP -->
                    <div class="item-wrapper mb-5">
                        <ul class="mt-2.5 flex flex-col items-center justify-center">
                            @if ($canViewDashboard)
                                <li class="item px-[43px] py-[11px] {{ $isDashboardActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                    <a href="{{ route('dashboard') }}">
                                        <span class="item-ico">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z" />
                                                <path d="M9 22V12h6v10" />
                                            </svg>
                                        </span>
                                    </a>
                                    <span class="sidebar-tooltip">Dashboard</span>
                                </li>
                            @endif

                            <li class="item px-[43px] py-[11px] {{ $isWorkspaceActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('user.workspace') }}">
                                    <span class="item-ico">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                        </svg>
                                    </span>
                                </a>
                                <span class="sidebar-tooltip">Workspace</span>
                            </li>

                            <li class="item px-[43px] py-[11px] {{ $isAnalyticsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('user.analytics') }}">
                                    <span class="item-ico">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="20" x2="18" y2="10" />
                                            <line x1="12" y1="20" x2="12" y2="4" />
                                            <line x1="6" y1="20" x2="6" y2="14" />
                                        </svg>
                                    </span>
                                </a>
                                <span class="sidebar-tooltip">Analytics</span>
                            </li>



                            @if ($canViewUsers)
                                <li class="item px-[43px] py-[11px] {{ $isUsersActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                    <a href="{{ route('users.index') }}">
                                        <span class="item-ico">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                                <circle cx="12" cy="7" r="4" />
                                            </svg>
                                        </span>
                                    </a>
                                    <span class="sidebar-tooltip">Users</span>
                                </li>
                            @endif

                            @if ($canViewTeams)
                                <li class="item px-[43px] py-[11px] {{ $isTeamsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                    <a href="{{ route('teams.index') }}">
                                        <span class="item-ico">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 21v-2a4 4 0 00-3-3.87" />
                                                <path d="M9 21v-2a4 4 0 00-4-4H3a4 4 0 00-4 4v2" />
                                                <circle cx="9" cy="7" r="4" />
                                                <path d="M23 21v-2a4 4 0 00-3-3.87" />
                                                <path d="M16 3.13a4 4 0 010 7.75" />
                                            </svg>
                                        </span>
                                    </a>
                                    <span class="sidebar-tooltip">Teams</span>
                                </li>
                            @endif

                            @if ($canViewCustomers)
                                <li class="item px-[43px] py-[11px] {{ $isCustomersActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                    <a href="{{ route('customers.index') }}">
                                        <span class="item-ico">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
                                                <circle cx="8.5" cy="7" r="4" />
                                                <polyline points="17 11 19 13 23 9" />
                                            </svg>
                                        </span>
                                    </a>
                                    <span class="sidebar-tooltip">Customers</span>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <!-- WORKSPACE GROUP -->
                    @if ($hasWorkspaceLinks)
                        <div class="item-wrapper mb-5">
                            <ul class="mt-2.5 flex flex-col items-center justify-center">
                                @if ($canViewProjects)
                                    <li class="item px-[43px] py-[11px] {{ $isProjectsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('projects.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                                                    <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Projects</span>
                                    </li>
                                @endif

                                @if ($canViewTasks)
                                    <li class="item px-[43px] py-[11px] {{ $isTasksActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('tasks.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M9 11l3 3L22 4" />
                                                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Tasks</span>
                                    </li>
                                @endif

                                @if ($canViewTasks)
                                    <li class="item px-[43px] py-[11px] {{ $isKanbanActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('tasks.kanban.view') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="5" height="18" rx="1" />
                                                    <rect x="10" y="3" width="5" height="12" rx="1" />
                                                    <rect x="17" y="3" width="4" height="7" rx="1" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Kanban</span>
                                    </li>
                                @endif

                                @if ($canViewTasks)
                                    <li class="item px-[43px] py-[11px] {{ $isScheduleTasksActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('schedule-tasks.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                    <polyline points="12 14 12 17 14 17" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Schedule Tasks</span>
                                    </li>
                                @endif

                                @if ($canViewTaskRequests || $canViewTaskTimeLogChangeRequests || $canViewHandoffs || $canViewBreakRequests || $canViewLeaveRequests)
                                    <li class="item px-[43px] py-[11px] relative {{ $isRequestsMenuActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="#">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                                    <path d="M13.73 21a2 2 0 01-3.46 0" />
                                                </svg>
                                            </span>
                                            @if ($requestMenuBadges['has_any_pending'] ?? false)
                                                <span class="absolute top-[8px] right-[32px] h-2.5 w-2.5 rounded-full border border-white bg-red-500 dark:border-none"></span>
                                            @endif
                                        </a>
                                        <span class="sidebar-tooltip">Requests</span>
                                        <ul class="sub-menu min-w-[200px] rounded-lg border-l border-success-100 bg-white px-5 py-2 shadow-lg dark:bg-darkblack-600 dark:border-darkblack-400">
                                            @if ($canViewTaskRequests)
                                                <li>
                                                    <a href="{{ route('tasks.requests.index') }}" class="text-md inline-flex items-center justify-between w-full py-1.5 font-medium transition-all {{ $isTaskRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        <span>Task</span>
                                                        @if (($requestMenuBadges['task_requests'] ?? 0) > 0)
                                                            <span class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                                                {{ $requestMenuBadges['task_requests'] }}
                                                            </span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewTaskTimeLogChangeRequests)
                                                <li>
                                                    <a href="{{ route('tasks.time-log-change-requests.index') }}" class="text-md inline-flex items-center justify-between w-full py-1.5 font-medium transition-all {{ $isTaskTimeChangeRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        <span>Task Log</span>
                                                        @if (($requestMenuBadges['task_time'] ?? 0) > 0)
                                                            <span class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                                                {{ $requestMenuBadges['task_time'] }}
                                                            </span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewHandoffs)
                                                <li>
                                                    <a href="{{ route('handoff_requests.index') }}" class="text-md inline-flex items-center justify-between w-full py-1.5 font-medium transition-all {{ $isHandoffsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        <span>Handoff</span>
                                                        @if (($requestMenuBadges['task_handoff'] ?? 0) > 0)
                                                            <span class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                                                {{ $requestMenuBadges['task_handoff'] }}
                                                            </span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewBreakRequests)
                                                <li>
                                                    <a href="{{ route('break-requests.index') }}" class="text-md inline-flex items-center justify-between w-full py-1.5 font-medium transition-all {{ $isBreakRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        <span>Break</span>
                                                        @if (($requestMenuBadges['break_requests'] ?? 0) > 0)
                                                            <span class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                                                {{ $requestMenuBadges['break_requests'] }}
                                                            </span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewTaskTimeExtendRequests)
                                                <li>
                                                    <a href="{{ route('tasks.extend-time-requests.index') }}" class="text-md inline-flex items-center justify-between w-full py-1.5 font-medium transition-all {{ $isTaskTimeExtendRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        <span>Time Extend</span>
                                                        @if (($requestMenuBadges['task_time_extend_requests'] ?? 0) > 0)
                                                            <span class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                                                {{ $requestMenuBadges['task_time_extend_requests'] }}
                                                            </span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewLeaveRequests)
                                                <li>
                                                    <a href="{{ route('leave-requests.pending') }}" class="text-md inline-flex items-center justify-between w-full py-1.5 font-medium transition-all {{ $isLeaveApprovalRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        <span>Leave Requests</span>
                                                        @if (($requestMenuBadges['leave_requests'] ?? 0) > 0)
                                                            <span class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                                                {{ $requestMenuBadges['leave_requests'] }}
                                                            </span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif

                                @if ($canViewMeetings)
                                    <li class="item px-[43px] py-[11px] {{ $isMeetingsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('meetings.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                    <circle cx="12" cy="15" r="2" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Meetings</span>
                                    </li>
                                @endif

                            </ul>
                        </div>
                        <div class="item-wrapper mb-5">
                            <ul class="mt-2.5 flex flex-col items-center justify-center">
                                @if ($canViewLeaveRequests)
                                    <li class="item px-[43px] py-[11px] {{ $isLeavesActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('leave-requests.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                    <line x1="8" y1="14" x2="16" y2="14" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Leaves</span>
                                    </li>
                                @endif

                                @if ($canViewAttendance)
                                    <li class="item px-[43px] py-[11px] {{ request()->routeIs('attendance.*') ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('attendance.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                                    <polyline points="9 16 11 18 15 14" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Attendance</span>
                                    </li>
                                @endif

                                @if ($canViewHolidays)
                                    <li class="item px-[43px] py-[11px] {{ request()->routeIs('holidays.*') ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('holidays.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M7 3V5M17 3V5M4 9H20M5 5H19C20.1 5 21 5.9 21 7V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V7C3 5.9 3.9 5 5 5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M8 13L10 15L14 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Holidays</span>
                                    </li>
                                @endif

                                @if ($canViewExpenses)
                                    <li class="item px-[43px] py-[11px] {{ $isExpensesActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ $expensesRoute }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Expenses</span>
                                    </li>
                                @endif
                                @if ($canViewAppraisal)
                                    <li class="item px-[43px] py-[11px] {{ $isAppraisalActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('appraisal.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Appraisal</span>
                                    </li>
                                @endif

                            </ul>
                        </div>
                    @endif

                    <!-- REPORTS GROUP -->
                    @if ($canViewReports)
                        <div class="item-wrapper mb-5">
                            <ul class="mt-2.5 flex flex-col items-center justify-center">
                                @if ($canViewProductivityReports || $canViewTimeTrackingReports || $canViewDailyReports)
                                    <li class="item px-[43px] py-[11px] {{ $isPerformanceReportsMenuActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="#">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                                                    <polyline points="17 6 23 6 23 12" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Performance Reports</span>
                                        <ul class="sub-menu min-w-[200px] rounded-lg border-l border-success-100 bg-white px-5 py-2 shadow-lg dark:bg-darkblack-600 dark:border-darkblack-400">
                                            @if ($canViewDailyReports)
                                                <li>
                                                    <a href="{{ route('reports.daily_time') }}" class="text-md inline-block py-1.5 font-medium transition-all {{ $isDailyReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        Daily Time
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewTimeTrackingReports)
                                                <li>
                                                    <a href="{{ route('reports.time_tracking') }}" class="text-md inline-block py-1.5 font-medium transition-all {{ $isTimeTrackingReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        Time Tracking
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewProductivityReports)
                                                <li>
                                                    <a href="{{ route('reports.productivity') }}" class="text-md inline-block py-1.5 font-medium transition-all {{ $isProductivityReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        Productivity
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif

                                @if ($canViewProjectReports || $canViewMilestoneReports || $canViewSprintReports || $canViewTaskReports)
                                    <li class="item px-[43px] py-[11px] {{ $isProjectsReportsMenuActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="#">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                                                    <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Project Reports</span>
                                        <ul class="sub-menu min-w-[200px] rounded-lg border-l border-success-100 bg-white px-5 py-2 shadow-lg dark:bg-darkblack-600 dark:border-darkblack-400">
                                            @if ($canViewProjectReports)
                                                <li>
                                                    <a href="{{ route('reports.projects') }}" class="text-md inline-block py-1.5 font-medium transition-all {{ $isProjectReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        Project
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewMilestoneReports)
                                                <li>
                                                    <a href="{{ route('reports.milestones') }}" class="text-md inline-block py-1.5 font-medium transition-all {{ $isMilestoneReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        Milestone
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewSprintReports)
                                                <li>
                                                    <a href="{{ route('reports.sprints') }}" class="text-md inline-block py-1.5 font-medium transition-all {{ $isSprintReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        Sprint
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canViewTaskReports)
                                                <li>
                                                    <a href="{{ route('reports.tasks') }}" class="text-md inline-block py-1.5 font-medium transition-all {{ $isTaskReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                        Task
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    <!-- CONFIGURATIONS GROUP -->
                    @if ($hasConfigurationLinks)
                        <div class="item-wrapper mb-5">
                            <ul class="mt-2.5 flex flex-col items-center justify-center">
                                @if ($canViewScheduleShift)
                                    <li class="item px-[43px] py-[11px] {{ $isScheduleShiftActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('schedule.shift.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10" />
                                                    <polyline points="12 6 12 12 16 14" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Schedule Shift</span>
                                    </li>
                                @endif

                                @if ($canViewSettings)
                                    <li class="item px-[43px] py-[11px] {{ $isSettingsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('settings.index') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="3" />
                                                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Settings</span>
                                    </li>
                                @endif

                                @if ($canViewActivityLog)
                                    <li class="item px-[43px] py-[11px] {{ $isActivityLogActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                        <a href="{{ route('activity.log') }}">
                                            <span class="item-ico">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 8v4l3 3" />
                                                    <path d="M3.05 11a9 9 0 11.5 4m-.5 5v-5h5" />
                                                </svg>
                                            </span>
                                        </a>
                                        <span class="sidebar-tooltip">Activity Log</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</aside>

<style>
    /* Collapsed Sidebar Tooltips */
    .sidebar-tooltip {
        position: absolute;
        left: 85px;
        top: 50%;
        transform: translateY(-50%);
        background-color: #0f172a;
        color: #ffffff;
        font-size: 12px;
        font-weight: 500;
        padding: 5px 10px;
        border-radius: 6px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.15s ease-in-out;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        z-index: 50;
    }

    .sidebar-tooltip::before {
        content: '';
        position: absolute;
        right: 100%;
        top: 50%;
        transform: translateY(-50%);
        border-width: 5px;
        border-style: solid;
        border-color: transparent #0f172a transparent transparent;
    }

    .dark .sidebar-tooltip {
        background-color: #1e293b;
        border: 1px solid #334155;
    }

    .dark .sidebar-tooltip::before {
        border-color: transparent #1e293b transparent transparent;
    }

    /* Show tooltip when hovering the anchor tag / button */
    .sidebar-wrapper-collapse .sidebar-body .nav-wrapper ul li.item a:hover+.sidebar-tooltip,
    .sidebar-wrapper-collapse .sidebar-body .nav-wrapper ul li.item button:hover+.sidebar-tooltip {
        opacity: 1;
    }
</style>

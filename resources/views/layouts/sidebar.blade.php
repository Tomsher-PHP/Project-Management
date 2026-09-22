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
    $canViewSettings = $authUser?->canAny($settingsPermissions) ?? false;

    $canViewActivityLog = $authUser?->can('activity_log.view');

    $canViewProjectReports = $authUser?->can('reports.project_view');
    $canViewMilestoneReports = $authUser?->can('reports.milestone_view');
    $canViewSprintReports = $authUser?->can('reports.sprint_view');
    $canViewTaskReports = $authUser?->can('reports.task_view');

    $canViewTimeTrackingReports = $authUser?->can('reports.time_tracking_view');
    $canViewDailyReports = $authUser?->can('reports.daily_time_view');
    $canViewProductivityReports = $authUser?->can('reports.productivity_view');

    $canViewAppraisal = $authUser?->can('appraisal.view');
    $canViewLeaveRequests = $authUser?->can('leave_request.view');
    $canViewAttendance = $authUser?->can('attendance.view');
    $canViewHolidays = $authUser?->can('holidays.view');
    $canViewMeetings = $authUser?->canAny(['meeting.view', 'meeting.view_all', 'meeting.create']);
    $canViewExpenses = $authUser?->canAny(['expense.view', 'expense.view_all', 'check_expense.view', 'check_expense.view_all', 'reimbursement.view', 'reimbursement.view_all']);
 
    $hasManagementLinks = $canViewUsers || $canViewTeams || $canViewCustomers;
    $hasWorkspaceLinks = $canViewProjects || $canViewTasks || $canViewMeetings || $canViewTaskRequests || $canViewTaskTimeLogChangeRequests || $canViewBreakRequests || $canViewLeaveRequests || $canViewAppraisal || $canViewAttendance || $canViewHolidays || $canViewExpenses;
    $hasConfigurationLinks = $canViewScheduleShift || $canViewSettings || $canViewActivityLog;
    $canViewReports = $canViewProjectReports || $canViewMilestoneReports || $canViewSprintReports || $canViewTaskReports || $canViewProductivityReports || $canViewTimeTrackingReports || $canViewDailyReports;

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
    $isLeaveApprovalRequestsActive = request()->routeIs('leave-requests.pending');
    $isLeavesActive = request()->routeIs('leave-requests.index');

    $isRequestsMenuActive = $isTaskRequestsActive || $isTaskTimeChangeRequestsActive || $isHandoffsActive || $isBreakRequestsActive || $isTaskTimeExtendRequestsActive || $isLeaveApprovalRequestsActive;
    $isTasksActive = request()->routeIs('tasks.*') && !$isKanbanActive && !$isTaskRequestsActive && !$isTaskTimeChangeRequestsActive && !$isTaskTimeExtendRequestsActive;

    $isProjectReportActive = request()->routeIs('reports.projects', 'reports.project.export', 'reports.projects.by-flow');
    $isMilestoneReportActive = request()->routeIs('reports.milestones', 'reports.milestone.export');
    $isSprintReportActive = request()->routeIs('reports.sprints', 'reports.sprint.export');
    $isTaskReportActive = request()->routeIs('reports.tasks', 'reports.task.export');
    $isProjectsReportsMenuActive = $isProjectReportActive || $isMilestoneReportActive || $isSprintReportActive || $isTaskReportActive;

    $isTimeTrackingReportActive = request()->routeIs('reports.time_tracking', 'reports.time_tracking.export');
    $isDailyReportActive = request()->routeIs('reports.daily_time', 'reports.daily_time.export');
    $isProductivityReportActive = request()->routeIs('reports.productivity', 'reports.productivity.*');
    $isPerformanceReportsMenuActive = $isProductivityReportActive || $isTimeTrackingReportActive || $isDailyReportActive;

    $isScheduleShiftActive = request()->routeIs('schedule.shift.*');
    $isSettingsActive = request()->routeIs('settings.*');
    $isActivityLogActive = request()->routeIs('activity.log*');

    $isAppraisalActive = request()->routeIs('appraisal.*');

    $sidebarItemActiveClass = 'text-success-400 dark:text-success-300';
    $sidebarItemInactiveClass = 'text-bgray-900 dark:text-white';
    $sidebarSubLinkActiveClass = 'text-success-400 dark:text-success-300';
    $sidebarSubLinkInactiveClass = 'text-bgray-600 dark:text-bgray-50 hover:text-bgray-800 hover:dark:text-success-300';
@endphp
<aside class="sidebar-wrapper fixed top-0 z-30 block h-full border-r border-bgray-200 bg-white dark:border-darkblack-500 dark:bg-darkblack-600 sm:hidden xl:block">
    <div class="sidebar-header relative z-30 flex h-[60px] w-full items-center border-b border-r border-bgray-200 pl-8 pb-4 dark:border-darkblack-400">
        <a href="{{ $canViewDashboard ? route('dashboard') : route('user.workspace') }}" class="flex items-center">
            <span class="relative inline-flex">
                <img src="{{ asset(config('assets.icons.logo')) }}" class="block h-10 w-auto dark:hidden" alt="logo" />
                <img src="{{ asset(config('assets.icons.logo_white')) }}" class="hidden h-10 w-auto dark:block" alt="logo" />

                <span class="absolute -bottom-4 -left-1 inline-flex shrink-0 rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                    {{ config('app.version') }}
                </span>
            </span>
        </a>
        <button type="button" class="drawer-btn absolute right-0 top-[8px]" title="Ctrl+b">
            <span>
                <svg width="16" height="40" viewBox="0 0 16 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 10C0 4.47715 4.47715 0 10 0H16V40H10C4.47715 40 0 35.5228 0 30V10Z" fill="#22C55E" />
                    <path d="M10 15L6 20.0049L10 25.0098" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </button>
    </div>
    <div class="sidebar-body overflow-style-none relative z-30 h-screen w-full overflow-y-scroll pb-[200px] pl-8 pt-3">

        <div class="nav-wrapper mb-[36px] pr-8">
            <div class="item-wrapper mb-5">
                <ul>
                    @if ($canViewDashboard)
                        <li class="item py-[8px] {{ $isDashboardActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                            <a href="{{ route('dashboard') }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z" />
                                                <path d="M9 22V12h6v10" />
                                            </svg>
                                        </span>
                                        <span class="item-text text-base font-medium leading-none {{ $isDashboardActive ? $sidebarItemActiveClass : '' }}">Dashboard</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endif
                    <li class="item py-[8px] {{ $isWorkspaceActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                        <a href="{{ route('user.workspace') }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                        </svg>
                                    </span>
                                    <span class="item-text text-base font-medium leading-none {{ $isWorkspaceActive ? $sidebarItemActiveClass : '' }}">Workspace</span>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li class="item py-[8px] {{ $isAnalyticsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                        <a href="{{ route('user.analytics') }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="20" x2="18" y2="10" />
                                            <line x1="12" y1="20" x2="12" y2="4" />
                                            <line x1="6" y1="20" x2="6" y2="14" />
                                        </svg>
                                    </span>
                                    <span class="item-text text-base font-medium leading-none {{ $isAnalyticsActive ? $sidebarItemActiveClass : '' }}">Analytics</span>
                                </div>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
            @if ($hasManagementLinks)
                <div class="item-wrapper mb-5">
                    <h4 class="border-b border-bgray-200 text-xs font-medium leading-6 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">
                        Access Control
                    </h4>
                    <ul class="mt-2.5">

                        @if ($canViewUsers)
                            <li class="item py-[8px] {{ $isUsersActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('users.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                                    <circle cx="12" cy="7" r="4" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isUsersActive ? $sidebarItemActiveClass : '' }}">Users</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif
                        @if ($canViewTeams)
                            <li class="item py-[8px] {{ $isTeamsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('teams.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17 21v-2a4 4 0 00-3-3.87" />
                                                    <path d="M9 21v-2a4 4 0 00-4-4H3a4 4 0 00-4 4v2" />
                                                    <circle cx="9" cy="7" r="4" />
                                                    <path d="M23 21v-2a4 4 0 00-3-3.87" />
                                                    <path d="M16 3.13a4 4 0 010 7.75" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isTeamsActive ? $sidebarItemActiveClass : '' }}">Teams</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif
                        @if ($canViewCustomers)
                            <li class="item py-[8px] {{ $isCustomersActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('customers.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
                                                    <circle cx="8.5" cy="7" r="4" />
                                                    <polyline points="17 11 19 13 23 9" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isCustomersActive ? $sidebarItemActiveClass : '' }}">Customers</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
            @if ($hasWorkspaceLinks)
                <div class="item-wrapper mb-5">
                    <h4 class="border-b border-bgray-200 text-xs font-medium leading-6 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">
                        Operations
                    </h4>
                    <ul class="mt-2.5">

                        @if ($canViewProjects)
                            <!-- Projects -->
                            <li class="item py-[8px] {{ $isProjectsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('projects.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                                                    <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isProjectsActive ? $sidebarItemActiveClass : '' }}">Projects</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewTasks)
                            <!-- Tasks -->
                            <li class="item py-[8px] {{ $isTasksActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('tasks.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M9 11l3 3L22 4" />
                                                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isTasksActive ? $sidebarItemActiveClass : '' }}">Tasks</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewTasks)
                            <!-- Kanban -->
                            <li class="item py-[8px] {{ $isKanbanActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('tasks.kanban.view') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="5" height="18" rx="1" />
                                                    <rect x="10" y="3" width="5" height="12" rx="1" />
                                                    <rect x="17" y="3" width="4" height="7" rx="1" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isKanbanActive ? $sidebarItemActiveClass : '' }}">Kanban</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewTasks)
                            <li class="item py-[8px] {{ $isScheduleTasksActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('schedule-tasks.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                    <polyline points="12 14 12 17 14 17" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isScheduleTasksActive ? $sidebarItemActiveClass : '' }}">Schedule Tasks</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewTaskRequests || $canViewTaskTimeLogChangeRequests || $canViewHandoffs || $canViewBreakRequests || $canViewLeaveRequests)
                            <!-- Requests -->
                            <li class="item py-[8px] {{ $isRequestsMenuActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="javascript:void(0)" aria-expanded="{{ $isRequestsMenuActive ? 'true' : 'false' }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                                    <path d="M13.73 21a2 2 0 01-3.46 0" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isRequestsMenuActive ? $sidebarItemActiveClass : '' }}">Requests</span>
                                            @if ($requestMenuBadges['has_any_pending'] ?? false)
                                                <span class="h-2 w-2 rounded-full border-1 border-white bg-red-500 dark:border-none ml-1"></span>
                                            @endif
                                        </div>
                                        <span class="flex items-center gap-2">
                                            <svg width="6" height="12" viewBox="0 0 6 12" fill="none" class="fill-current transition-transform {{ $isRequestsMenuActive ? 'rotate-90 ' . $sidebarItemActiveClass : '' }}" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor" d="M0.531506 0.414376C0.20806 0.673133 0.155619 1.1451 0.414376 1.46855L4.03956 6.00003L0.414376 10.5315C0.155618 10.855 0.208059 11.3269 0.531506 11.5857C0.854952 11.8444 1.32692 11.792 1.58568 11.4685L5.58568 6.46855C5.80481 6.19464 5.80481 5.80542 5.58568 5.53151L1.58568 0.531506C1.32692 0.20806 0.854953 0.155619 0.531506 0.414376Z" />
                                            </svg>
                                        </span>
                                    </div>
                                </a>
                                <ul class="sub-menu ml-2.5 mt-[22px] border-l border-success-100 pl-5 {{ $isRequestsMenuActive ? 'active' : '' }}">
                                    @if ($canViewTaskRequests)
                                        <!-- Task Requests -->
                                        <li>
                                            <a href="{{ route('tasks.requests.index') }}" class="text-sm inline-flex items-center justify-between gap-2 py-1.5 font-medium transition-all {{ $isTaskRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
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
                                        <!-- Task Time Log Change Requests -->
                                        <li>
                                            <a href="{{ route('tasks.time-log-change-requests.index') }}" class="text-sm inline-flex items-center justify-between gap-2 py-1.5 font-medium transition-all {{ $isTaskTimeChangeRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
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
                                        <!-- Handoff Requests -->
                                        <li>
                                            <a href="{{ route('handoff_requests.index') }}" class="text-sm inline-flex items-center justify-between gap-2 py-1.5 font-medium transition-all {{ $isHandoffsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
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
                                        <!-- Break Requests -->
                                        <li>
                                            <a href="{{ route('break-requests.index') }}" class="text-sm inline-flex items-center justify-between gap-2 py-1.5 font-medium transition-all {{ $isBreakRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
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
                                        <!-- Task Time Extend Requests -->
                                        <li>
                                            <a href="{{ route('tasks.extend-time-requests.index') }}" class="text-sm inline-flex items-center justify-between gap-2 py-1.5 font-medium transition-all {{ $isTaskTimeExtendRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
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
                                        <!-- Leave Requests -->
                                        <li>
                                            <a href="{{ route('leave-requests.pending') }}" class="text-sm inline-flex items-center justify-between gap-2 py-1.5 font-medium transition-all {{ $isLeaveApprovalRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
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

                        @if ($canViewAppraisal)
                            <!-- Appraisal -->
                            <li class="item py-[8px] {{ $isAppraisalActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('appraisal.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isAppraisalActive ? $sidebarItemActiveClass : '' }}">Appraisal</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewLeaveRequests)
                            <!-- Leave Requests -->
                            <li class="item py-[8px] {{ $isLeavesActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('leave-requests.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                    <line x1="8" y1="14" x2="16" y2="14" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isLeavesActive ? $sidebarItemActiveClass : '' }}">
                                                Leaves
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewAttendance)
                            <!-- Attendance -->
                            <li class="item py-[8px] {{ request()->routeIs('attendance.*') ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('attendance.index') }}">
                                    <div class="flex items-center">
                                        <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                                <polyline points="9 16 11 18 15 14" />
                                            </svg>
                                        </span>
                                        <span class="item-text text-base font-medium leading-none">
                                            Attendance
                                        </span>
                                    </div>
                                </a>
                            </li>
                        @endif


                        @if ($canViewHolidays)
                            <!-- Holidays -->
                            <li class="item py-[8px] {{ request()->routeIs('holidays.*') ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('holidays.index') }}">
                                    <div class="flex items-center">
                                        <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M7 3V5M17 3V5M4 9H20M5 5H19C20.1 5 21 5.9 21 7V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V7C3 5.9 3.9 5 5 5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M8 13L10 15L14 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="item-text text-base font-medium leading-none">
                                            Holidays
                                        </span>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewMeetings)
                            <!-- Meetings -->
                            <li class="item py-[8px] {{ $isMeetingsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('meetings.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                    <circle cx="12" cy="15" r="2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isMeetingsActive ? $sidebarItemActiveClass : '' }}">Meetings</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewExpenses)
                            <!-- Company Expenses -->
                            <li class="item py-[8px] {{ $isExpensesActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('expenses.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isExpensesActive ? $sidebarItemActiveClass : '' }}">Expenses</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
            @if ($canViewReports)
                <!-- Reports -->
                <div class="item-wrapper mb-5">
                    <h4 class="border-b border-bgray-200 text-xs font-medium leading-6 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">
                        Reports
                    </h4>

                    <ul class="mt-2.5">

                        <!-- PERFORMANCE -->
                        @if ($canViewTimeTrackingReports || $canViewDailyReports || $canViewProductivityReports)
                            <li class="item py-[8px] {{ $isPerformanceReportsMenuActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="javascript:void(0)" aria-expanded="{{ $isPerformanceReportsMenuActive ? 'true' : 'false' }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                                                    <polyline points="17 6 23 6 23 12" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isPerformanceReportsMenuActive ? $sidebarItemActiveClass : '' }}">Performance</span>
                                        </div>
                                        <span class="flex items-center gap-2">
                                            <svg width="6" height="12" viewBox="0 0 6 12" fill="none" class="fill-current transition-transform {{ $isPerformanceReportsMenuActive ? 'rotate-90 ' . $sidebarItemActiveClass : '' }}" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor" d="M0.531506 0.414376C0.20806 0.673133 0.155619 1.1451 0.414376 1.46855L4.03956 6.00003L0.414376 10.5315C0.155618 10.855 0.208059 11.3269 0.531506 11.5857C0.854952 11.8444 1.32692 11.792 1.58568 11.4685L5.58568 6.46855C5.80481 6.19464 5.80481 5.80542 5.58568 5.53151L1.58568 0.531506C1.32692 0.20806 0.854953 0.155619 0.531506 0.414376Z" />
                                            </svg>
                                        </span>
                                    </div>
                                </a>
                                <ul class="sub-menu ml-2.5 mt-[22px] border-l border-success-100 pl-5 {{ $isPerformanceReportsMenuActive ? 'active' : '' }}">
                                    @if ($canViewTimeTrackingReports)
                                        <!-- Time Tracking Report -->
                                        <li>
                                            <a href="{{ route('reports.time_tracking') }}" class="text-sm inline-block py-1.5 font-medium transition-all {{ $isTimeTrackingReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                Time Tracking
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewDailyReports)
                                        <!-- Daily Time Report -->
                                        <li>
                                            <a href="{{ route('reports.daily_time') }}" class="text-sm inline-block py-1.5 font-medium transition-all {{ $isDailyReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                Daily Time
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewProductivityReports)
                                        <!-- Productivity Report -->
                                        <li>
                                            <a href="{{ route('reports.productivity') }}" class="text-sm inline-block py-1.5 font-medium transition-all {{ $isProductivityReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                Productivity
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        <!-- PROJECTS -->
                        @if ($canViewProjectReports || $canViewMilestoneReports || $canViewSprintReports || $canViewTaskReports)
                            <li class="item py-[8px] {{ $isProjectsReportsMenuActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="javascript:void(0)" aria-expanded="{{ $isProjectsReportsMenuActive ? 'true' : 'false' }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                                                    <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isProjectsReportsMenuActive ? $sidebarItemActiveClass : '' }}">Projects</span>
                                        </div>
                                        <span class="flex items-center gap-2">
                                            <svg width="6" height="12" viewBox="0 0 6 12" fill="none" class="fill-current transition-transform {{ $isProjectsReportsMenuActive ? 'rotate-90 ' . $sidebarItemActiveClass : '' }}" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor" d="M0.531506 0.414376C0.20806 0.673133 0.155619 1.1451 0.414376 1.46855L4.03956 6.00003L0.414376 10.5315C0.155618 10.855 0.208059 11.3269 0.531506 11.5857C0.854952 11.8444 1.32692 11.792 1.58568 11.4685L5.58568 6.46855C5.80481 6.19464 5.80481 5.80542 5.58568 5.53151L1.58568 0.531506C1.32692 0.20806 0.854953 0.155619 0.531506 0.414376Z" />
                                            </svg>
                                        </span>
                                    </div>
                                </a>
                                <ul class="sub-menu ml-2.5 mt-[22px] border-l border-success-100 pl-5 {{ $isProjectsReportsMenuActive ? 'active' : '' }}">
                                    @if ($canViewProjectReports)
                                        <!-- Project Report -->
                                        <li>
                                            <a href="{{ route('reports.projects') }}" class="text-sm inline-block py-1.5 font-medium transition-all {{ $isProjectReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                Project
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewMilestoneReports)
                                        <!-- Milestone Report -->
                                        <li>
                                            <a href="{{ route('reports.milestones') }}" class="text-sm inline-block py-1.5 font-medium transition-all {{ $isMilestoneReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                Milestone
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewSprintReports)
                                        <!-- Sprint Report -->
                                        <li>
                                            <a href="{{ route('reports.sprints') }}" class="text-sm inline-block py-1.5 font-medium transition-all {{ $isSprintReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
                                                Sprint
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewTaskReports)
                                        <!-- Task Report -->
                                        <li>
                                            <a href="{{ route('reports.tasks') }}" class="text-sm inline-block py-1.5 font-medium transition-all {{ $isTaskReportActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}">
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
            @if ($hasConfigurationLinks)
                <div class="item-wrapper mb-5">
                    <h4 class="border-b border-bgray-200 text-xs font-medium leading-6 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">
                        System & Logs
                    </h4>
                    <ul class="mt-2.5">

                        @if ($canViewScheduleShift)
                            <!-- Schedule Shift -->
                            <li class="item py-[8px] {{ $isScheduleShiftActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('schedule.shift.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10" />
                                                    <polyline points="12 6 12 12 16 14" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isScheduleShiftActive ? $sidebarItemActiveClass : '' }}">Schedule Shift</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewSettings)
                            <!-- Settings -->
                            <li class="item py-[8px] {{ $isSettingsActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('settings.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="3" />
                                                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isSettingsActive ? $sidebarItemActiveClass : '' }}">Settings</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewActivityLog)
                            <!-- Activity Log -->
                            <li class="item py-[8px] {{ $isActivityLogActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('activity.log') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 8v4l3 3" />
                                                    <path d="M3.05 11a9 9 0 11.5 4m-.5 5v-5h5" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isActivityLogActive ? $sidebarItemActiveClass : '' }}">Activity Log</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                    </ul>
                </div>
            @endif
        </div>
        <!-- Sidebar User Footer -->
        <div class="sidebar-user-footer shrink-0 z-40 w-full border-t border-bgray-200 py-4 pr-8 dark:border-darkblack-400">
            <div class="flex items-center gap-3">
                <x-user-avatar :user="$authUser" size="sm" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-bgray-900 dark:text-white">
                        {{ $authUser?->name }}
                    </p>
                    <p class="truncate text-xs font-medium text-bgray-700 dark:text-bgray-300">
                        {{ $authUser?->role_name ?? 'No Role' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</aside>

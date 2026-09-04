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
    $canViewSettings = collect($settingsPermissions)->contains(fn($permission) => auth()->user()->can($permission));

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

    $hasManagementLinks = $canViewRoles || $canViewUsers || $canViewTeams || $canViewCustomers;
    $hasWorkspaceLinks = $canViewProjects || $canViewTasks || $canViewTaskRequests || $canViewTaskTimeLogChangeRequests || $canViewBreakRequests || $canViewLeaveRequests || $canViewAppraisal;
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


    $isRequestsMenuActive = $isTaskRequestsActive || $isTaskTimeChangeRequestsActive || $isHandoffsActive || $isBreakRequestsActive || $isTaskTimeExtendRequestsActive  ||
    $isLeaveApprovalRequestsActive;
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
<aside class="sidebar-wrapper fixed top-0 z-30 block h-full bg-white dark:bg-darkblack-600 sm:hidden xl:block">
    <div class="sidebar-header relative z-30 flex h-[60px] w-full items-center border-b border-r border-b-[#F7F7F7] border-r-[#F7F7F7] pl-8 pb-4 dark:border-darkblack-400">
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
                                            <svg width="16" height="18" viewBox="0 0 18 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="path-1" d="M0 8.84719C0 7.99027 0.366443 7.17426 1.00691 6.60496L6.34255 1.86217C7.85809 0.515019 10.1419 0.515019 11.6575 1.86217L16.9931 6.60496C17.6336 7.17426 18 7.99027 18 8.84719V17C18 19.2091 16.2091 21 14 21H4C1.79086 21 0 19.2091 0 17V8.84719Z" fill="#1A202C" />
                                                <path class="path-2" d="M5 17C5 14.7909 6.79086 13 9 13C11.2091 13 13 14.7909 13 17V21H5V17Z" fill="#22C55E" />
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
                                        <svg width="16" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="3" y="3" width="7" height="7" rx="1.5" fill="#1A202C" class="path-1" />
                                            <rect x="14" y="3" width="7" height="7" rx="1.5" fill="#22C55E" class="path-2" />
                                            <rect x="3" y="14" width="7" height="7" rx="1.5" fill="#1A202C" class="path-1" />
                                            <rect x="14" y="14" width="7" height="7" rx="1.5" fill="#1A202C" class="path-1" />
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
                                        <svg width="16" height="18" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0 4C0 1.8 1.8 0 4 0H14C16.2 0 18 1.8 18 4V16C18 18.2 16.2 20 14 20H4C1.8 20 0 18.2 0 16V4Z" fill="#1A202C" class="path-1" />
                                            <path d="M6.5 6C5.4 6 4.5 6.9 4.5 8V14C4.5 15.1 5.4 16 6.5 16C7.6 16 8.5 15.1 8.5 14V8C8.5 6.9 7.6 6 6.5 6Z" fill="#22C55E" class="path-2" />
                                            <path d="M12.5 10C11.4 10 10.5 10.9 10.5 12V14C10.5 15.1 11.4 16 12.5 16C13.6 16 14.5 15.1 14.5 14V12C14.5 10.9 13.6 10 12.5 10Z" fill="#22C55E" class="path-2" />
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
                        @if ($canViewRoles)
                            <li class="item py-[8px] {{ $isRolesActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('roles.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="16" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 2L4 5V11C4 16.52 7.38 20.62 12 22C16.62 20.62 20 16.52 20 11V5L12 2ZM18 11C18 15.42 15.46 18.72 12 20C8.54 18.72 6 15.42 6 11V6.3L12 4.05L18 6.3V11Z" fill="#1A202C" class="path-1" />
                                                    <path d="M10 15.17L6.7 11.87L8.11 10.45L10 12.34L14.68 7.66L16.1 9.08L10 15.17Z" fill="#22C55E" class="path-2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-base font-medium leading-none {{ $isRolesActive ? $sidebarItemActiveClass : '' }}">Roles</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif
                        @if ($canViewUsers)
                            <li class="item py-[8px] {{ $isUsersActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('users.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                                <svg width="16" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <ellipse cx="11.7778" cy="17.5555" rx="7.77778" ry="4.44444" class="path-1" fill="#1A202C" />
                                                    <circle class="path-2" cx="11.7778" cy="6.44444" r="4.44444" fill="#22C55E" />
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
                                                <svg width="16" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="6" cy="8" r="2.5" fill="#1A202C" class="path-1" />
                                                    <path d="M6 12C4.2 12 0.5 12.9 0.5 14.7V18H11.5V14.7C11.5 12.9 7.8 12 6 12Z" fill="#1A202C" class="path-1" />
                                                    <circle cx="18" cy="8" r="2.5" fill="#1A202C" class="path-1" />
                                                    <path d="M18 12C16.2 12 12.5 12.9 12.5 14.7V18H23.5V14.7C23.5 12.9 19.8 12 18 12Z" fill="#1A202C" class="path-1" />
                                                    <path d="M12 11C9.5 11 4.5 12.2 4.5 14.8V18H19.5V14.8C19.5 12.2 14.5 11 12 11Z" fill="#1A202C" class="path-1" />
                                                    <circle cx="12" cy="6" r="3.5" fill="#22C55E" class="path-2" />
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
                                                <svg width="16" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M9 12C6 12 1 13.2 1 15.8V19H17V15.8C17 13.2 12 12 9 12Z" fill="#1A202C" class="path-1" />
                                                    <circle cx="9" cy="7" r="3.5" fill="#22C55E" class="path-2" />
                                                    <path d="M19 2L20.25 5.82H24.27L21.02 8.18L22.26 12L19 9.63L15.74 12L16.98 8.18L12.98 5.82H17.75L19 2Z" fill="#22C55E" class="path-2" />
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
                                                <svg width="16" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M2 7C2 5.9 2.9 5 4 5H16C17.1 5 18 5.9 18 7V16C18 17.1 17.1 18 16 18H4C2.9 18 2 17.1 2 16V7Z" fill="#1A202C" class="path-1" />
                                                    <path d="M7 5V3C7 1.9 7.9 1 9 1H11C12.1 1 13 1.9 13 3V5H11V3H9V5H7ZM8.5 10C8.5 9.45 8.95 9 9.5 9H10.5C11.05 9 11.5 9.45 11.5 10V11.5H8.5V10Z" fill="#22C55E" class="path-2" />
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
                                                <svg width="16" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M4 3C4 1.9 4.9 1 6 1H14C15.1 1 16 1.9 16 3V16C16 17.1 15.1 18 14 18H6C4.9 18 4 17.1 4 16V3Z" fill="#1A202C" class="path-1" />
                                                    <path d="M8 1C8 0.45 8.45 0 9 0H11C11.55 0 12 0.45 12 1V2H8V1ZM5.5 7.5L7 9L11 5L12 6L7 11L4.5 8.5L5.5 7.5ZM13 7H15V8H13V7ZM5.5 12.5L7 14L11 10L12 11L7 16L4.5 13.5L5.5 12.5ZM13 12H15V13H13V12Z" fill="#22C55E" class="path-2" />
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
                                                <svg width="16" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect x="1" y="2" width="5" height="14" rx="1.5" fill="#1A202C" class="path-1" />
                                                    <rect x="7.5" y="2" width="5" height="14" rx="1.5" fill="#1A202C" class="path-1" />
                                                    <rect x="14" y="2" width="5" height="14" rx="1.5" fill="#1A202C" class="path-1" />
                                                    <rect x="2" y="4" width="3" height="3" rx="0.5" fill="#22C55E" class="path-2" />
                                                    <rect x="2" y="9" width="3" height="4" rx="0.5" fill="#22C55E" class="path-2" />
                                                    <rect x="8.5" y="5" width="3" height="5" rx="0.5" fill="#22C55E" class="path-2" />
                                                    <rect x="8.5" y="11" width="3" height="3" rx="0.5" fill="#22C55E" class="path-2" />
                                                    <rect x="15" y="4" width="3" height="4" rx="0.5" fill="#22C55E" class="path-2" />
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
                                                <svg width="16" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect x="2" y="3" width="16" height="14" rx="2" fill="#1A202C" class="path-1" />
                                                    <path d="M6 1V5M14 1V5M2 7H18M7 11H10V14H7V11Z" stroke="#22C55E" stroke-width="2" class="path-2" />
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
                                                <svg width="16" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10 2.5C7.2 2.5 5 4.7 5 7.5V13H3V15H17V13H15V7.5C15 4.7 12.8 2.5 10 2.5Z" fill="#1A202C" class="path-1" />
                                                    <path d="M9 1C9 0.45 9.45 0 10 0C10.55 0 11 0.45 11 1V2.5H9V1ZM8 16C8 17.1 8.9 18 10 18C11.1 18 12 17.1 12 16H8Z" fill="#22C55E" class="path-2" />
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
                                            <a
                                                href="{{ route('leave-requests.pending') }}"
                                                class="text-sm inline-flex items-center justify-between gap-2 py-1.5 font-medium transition-all {{ $isLeaveApprovalRequestsActive ? $sidebarSubLinkActiveClass : $sidebarSubLinkInactiveClass }}"
                                            >
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
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                    <path d="M12 2L14.9 8.3L22 9.2L17 14L18.2 21L12 17.7L5.8 21L7 14L2 9.2L9.1 8.3L12 2Z" fill="#1A202C" class="path-1" />
                                                    <path d="M12 5.2L13.7 9L17.8 9.4L14.8 12.2L15.6 16.2L12 14.2V5.2Z" fill="#22C55E" class="path-2" />
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
                                                <svg
                                                    width="16"
                                                    height="18"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <path
                                                        d="M7 3V5M17 3V5M4 9H20M5 5H19C20.1 5 21 5.9 21 7V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V7C3 5.9 3.9 5 5 5Z"
                                                        stroke="#1A202C"
                                                        stroke-width="1.8"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />

                                                    <path
                                                        d="M8 13H10M14 13H16M8 17H10M14 17H16"
                                                        stroke="#22C55E"
                                                        stroke-width="1.8"
                                                        stroke-linecap="round"
                                                    />
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
                                            <svg
                                                width="18"
                                                height="18"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M7 3V5M17 3V5M4 9H20M5 5H19C20.1 5 21 5.9 21 7V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V7C3 5.9 3.9 5 5 5Z"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                                <path
                                                    d="M8 13L10 15L14 11"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                        </span>
                                        <span class="item-text text-base font-medium leading-none">
                                            Attendance
                                        </span>
                                    </div>
                                </a>
                            </li>
                        @endif
                        {{-- @if ($canViewAttendance) --}}
                            <!-- Holidays -->
                            <li class="item py-[8px]">
                                <a href="{{ route('holidays.index') }}">
                                    <div class="flex items-center">
                                        <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                            <svg
                                                width="18"
                                                height="18"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M7 3V5M17 3V5M4 9H20M5 5H19C20.1 5 21 5.9 21 7V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V7C3 5.9 3.9 5 5 5Z"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                                <path
                                                    d="M8 13L10 15L14 11"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                        </span>
                                        <span class="item-text text-base font-medium leading-none">
                                            Holidays
                                        </span>
                                    </div>
                                </a>
                            </li>
                        {{-- @endif --}}
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
                                                <svg width="16" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M2 14L6 10L9 13L15 6L18 8" stroke="#22C55E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="path-2" />
                                                    <circle cx="2" cy="14" r="2" fill="#1A202C" class="path-1" />
                                                    <circle cx="6" cy="10" r="2" fill="#1A202C" class="path-1" />
                                                    <circle cx="9" cy="13" r="2" fill="#1A202C" class="path-1" />
                                                    <circle cx="15" cy="6" r="2" fill="#1A202C" class="path-1" />
                                                    <circle cx="18" cy="8" r="2" fill="#22C55E" class="path-2" />
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
                                                <svg width="16" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M2 7C2 5.9 2.9 5 4 5H16C17.1 5 18 5.9 18 7V16C18 17.1 17.1 18 16 18H4C2.9 18 2 17.1 2 16V7Z" fill="#1A202C" class="path-1" />
                                                    <path d="M7 5V3C7 1.9 7.9 1 9 1H11C12.1 1 13 1.9 13 3V5H11V3H9V5H7ZM8.5 10C8.5 9.45 8.95 9 9.5 9H10.5C11.05 9 11.5 9.45 11.5 10V11.5H8.5V10Z" fill="#22C55E" class="path-2" />
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
                                                <svg width="16" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M2 5C2 3.9 2.9 3 4 3H16C17.1 3 18 3.9 18 5V16C18 17.1 17.1 18 16 18H4C2.9 18 2 17.1 2 16V5Z" fill="#1A202C" class="path-1" />
                                                    <path d="M16 3H4C2.9 3 2 3.9 2 5V7H18V5C18 3.9 17.1 3 16 3ZM5 1.5C5 0.67 5.67 0 6.5 0C7.33 0 8 0.67 8 1.5V4.5C8 5.33 7.33 6 6.5 6C5.67 6 5 5.33 5 4.5V1.5ZM12 1.5C12 0.67 12.67 0 13.5 0C14.33 0 15 0.67 15 1.5V4.5C15 5.33 14.33 6 13.5 6C12.67 6 12 5.33 12 4.5V1.5Z" fill="#22C55E" class="path-2" />
                                                    <circle cx="6" cy="11" r="1" fill="#22C55E" class="path-2" />
                                                    <circle cx="10" cy="11" r="1" fill="#22C55E" class="path-2" />
                                                    <circle cx="14" cy="11" r="1" fill="#22C55E" class="path-2" />
                                                    <circle cx="6" cy="14" r="1" fill="#22C55E" class="path-2" />
                                                    <circle cx="10" cy="14" r="1" fill="#22C55E" class="path-2" />
                                                    <circle cx="14" cy="14" r="1" fill="#22C55E" class="path-2" />
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
                                                <svg width="18" height="18" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M13.0606 2H10.9394C9.76787 2 8.81817 2.89543 8.81817 4C8.81817 5.26401 7.46574 6.06763 6.35556 5.4633L6.24279 5.40192C5.22823 4.84963 3.93091 5.17738 3.34515 6.13397L2.28455 7.86602C1.69879 8.8226 2.0464 10.0458 3.06097 10.5981C4.17168 11.2027 4.17168 12.7973 3.06096 13.4019C2.0464 13.9542 1.69879 15.1774 2.28454 16.134L3.34515 17.866C3.93091 18.8226 5.22823 19.1504 6.24279 18.5981L6.35555 18.5367C7.46574 17.9324 8.81817 18.736 8.81817 20C8.81817 21.1046 9.76787 22 10.9394 22H13.0606C14.2321 22 15.1818 21.1046 15.1818 20C15.1818 18.736 16.5343 17.9324 17.6445 18.5367L17.7572 18.5981C18.7718 19.1504 20.0691 18.8226 20.6548 17.866L21.7155 16.134C22.3012 15.1774 21.9536 13.9542 20.939 13.4019C19.8283 12.7973 19.8283 11.2027 20.939 10.5981C21.9536 10.0458 22.3012 8.82262 21.7155 7.86603L20.6548 6.13398C20.0691 5.1774 18.7718 4.84965 17.7572 5.40193L17.6445 5.46331C16.5343 6.06765 15.1818 5.26402 15.1818 4C15.1818 2.89543 14.2321 2 13.0606 2Z" fill="#1A202C" class="path-1" />
                                                    <path d="M15.75 12C15.75 14.0711 14.0711 15.75 12 15.75C9.92893 15.75 8.25 14.0711 8.25 12C8.25 9.92893 9.92893 8.25 12 8.25C14.0711 8.25 15.75 9.92893 15.75 12Z" fill="#22C55E" class="path-2" />
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
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M13 3C8.03 3 4 7.03 4 12H1L4.89 15.89L5 16L9 12H6C6 8.13 9.13 5 13 5C16.87 5 20 8.13 20 12C20 15.87 16.87 19 13 19C11.07 19 9.32 18.21 8.06 16.94L6.64 18.36C8.27 20 10.51 21 13 21C17.97 21 22 16.97 22 12C22 7.03 17.97 3 13 3Z" fill="#1A202C" class="path-1" />
                                                    <path d="M12.5 7V12.5L16 14.6L16.8 13.3L14 11.6V7H12.5Z" fill="#22C55E" class="path-2" />
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

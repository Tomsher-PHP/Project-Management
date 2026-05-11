<aside class="sidebar-wrapper fixed top-0 z-30 block h-full bg-white dark:bg-darkblack-600 sm:hidden xl:block">
    <div class="sidebar-header relative z-30 flex h-[60px] w-full items-center border-b border-r border-b-[#F7F7F7] border-r-[#F7F7F7] pl-8 dark:border-darkblack-400">
        <a href="{{ route('dashboard') }}">
            <img src="{{ asset(config('assets.icons.logo')) }}" class="block dark:hidden" alt="logo" />
            <img src="{{ asset(config('assets.icons.logo_white')) }}" class="hidden dark:block" alt="logo" />
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
        @php
            $authUser = auth()->user();

            $canViewRoles = $authUser?->can('role.view');
            $canViewUsers = $authUser?->can('user.view');
            $canViewTeams = $authUser?->can('team.view');
            $canViewCustomers = $authUser?->can('customer.view');

            $canViewProjects = $authUser?->can('project.view');
            $canViewTasks = $authUser?->can('task.view');
            $canViewTaskRequests = $canViewTasks;
            $canViewTaskTimeLogChangeRequests = $authUser?->can('task_time_log_change_request.approve_reject');

            $canViewScheduleShift = $authUser?->can('schedule_shift.view');

            $settingsPermissions = config('constants.settings_permissions');
            $canViewSettings = collect($settingsPermissions)->contains(fn($permission) => auth()->user()->can($permission));

            $canViewActivityLog = $authUser?->can('activity_log.view');

            
            

            $canViewProjectReports = $authUser?->can('reports.project_view');
            $canViewTaskReports = $authUser?->can('reports.task_view');
            $canViewTimeTrackingReports = $authUser?->can('reports.time_tracking_view');
            $canViewAttendanceReports = $authUser?->can('reports.attendance_view');
            $canViewDailyReports = $authUser?->can('reports.daily_view');
            $canViewShiftScheduleReports = $authUser?->can('reports.shift_schedule_view');
            $canViewProductivityReports = $authUser?->can('reports.productivity_view');
            $canViewSprintReports = $authUser?->can('reports.sprint_view');
            $canViewMilestoneReports = $authUser?->can('reports.milestone_view');
            $canViewLeaveReports = $authUser?->can('reports.leave_view');

            $hasManagementLinks = $canViewRoles || $canViewUsers || $canViewTeams || $canViewCustomers;
            $hasWorkspaceLinks = $canViewProjects || $canViewTasks || $canViewTaskRequests || $canViewTaskTimeLogChangeRequests;
            $hasConfigurationLinks = $canViewScheduleShift || $canViewSettings || $canViewActivityLog;
            $canViewReports = $canViewProductivityReports || $canViewTimeTrackingReports || $canViewDailyReports || $canViewAttendanceReports || $canViewLeaveReports || $canViewShiftScheduleReports || $canViewProjectReports || $canViewMilestoneReports || $canViewSprintReports || $canViewTaskReports;
        @endphp

        <div class="nav-wrapper mb-[36px] pr-8">
            <div class="item-wrapper mb-5">
                <h4 class="border-b border-bgray-200 text-sm font-medium leading-7 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">
                    Menu
                </h4>
                <ul class="mt-2.5">
                    <li class="item py-[11px] text-bgray-900 dark:text-white">
                        <a href="index.html">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <span class="item-ico">
                                        <svg width="18" height="21" viewBox="0 0 18 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path class="path-1" d="M0 8.84719C0 7.99027 0.366443 7.17426 1.00691 6.60496L6.34255 1.86217C7.85809 0.515019 10.1419 0.515019 11.6575 1.86217L16.9931 6.60496C17.6336 7.17426 18 7.99027 18 8.84719V17C18 19.2091 16.2091 21 14 21H4C1.79086 21 0 19.2091 0 17V8.84719Z" fill="#1A202C" />
                                            <path class="path-2" d="M5 17C5 14.7909 6.79086 13 9 13C11.2091 13 13 14.7909 13 17V21H5V17Z" fill="#22C55E" />
                                        </svg>
                                    </span>
                                    <span class="item-text text-lg font-medium leading-none">Dashboards</span>
                                </div>
                                <span>
                                    <svg width="6" height="12" viewBox="0 0 6 12" fill="none" class="fill-current" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor" d="M0.531506 0.414376C0.20806 0.673133 0.155619 1.1451 0.414376 1.46855L4.03956 6.00003L0.414376 10.5315C0.155618 10.855 0.208059 11.3269 0.531506 11.5857C0.854952 11.8444 1.32692 11.792 1.58568 11.4685L5.58568 6.46855C5.80481 6.19464 5.80481 5.80542 5.58568 5.53151L1.58568 0.531506C1.32692 0.20806 0.854953 0.155619 0.531506 0.414376Z" />
                                    </svg>
                                </span>
                            </div>
                        </a>
                        <ul class="sub-menu ml-2.5 mt-[22px] border-l border-success-100 pl-5">
                            <li>
                                <a href="{{ route('dashboard') }}" class="text-md inline-block py-1.5 font-medium text-bgray-600 transition-all hover:text-bgray-800 dark:text-bgray-50 hover:dark:text-success-300">Dashboard
                                    Default</a>
                            </li>
                            <li>
                                <a href="{{ route('user.workspace') }}" class="text-md inline-block py-1.5 font-medium text-bgray-600 transition-all hover:text-bgray-800 dark:text-bgray-50 hover:dark:text-success-300">Workspace</a>
                            </li>
                        </ul>
                    </li>
                    @if ($canViewRoles)
                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                            <a href="{{ route('roles.index') }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5">
                                        <span class="item-ico">
                                            <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18 16V6C18 3.79086 16.2091 2 14 2H4C1.79086 2 0 3.79086 0 6V16C0 18.2091 1.79086 20 4 20H14C16.2091 20 18 18.2091 18 16Z" fill="#1A202C" class="path-1" />
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4.25 8C4.25 7.58579 4.58579 7.25 5 7.25H13C13.4142 7.25 13.75 7.58579 13.75 8C13.75 8.41421 13.4142 8.75 13 8.75H5C4.58579 8.75 4.25 8.41421 4.25 8Z" fill="#22C55E" class="path-2" />
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4.25 12C4.25 11.5858 4.58579 11.25 5 11.25H13C13.4142 11.25 13.75 11.5858 13.75 12C13.75 12.4142 13.4142 12.75 13 12.75H5C4.58579 12.75 4.25 12.4142 4.25 12Z" fill="#22C55E" class="path-2" />
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4.25 16C4.25 15.5858 4.58579 15.25 5 15.25H9C9.41421 15.25 9.75 15.5858 9.75 16C9.75 16.4142 9.41421 16.75 9 16.75H5C4.58579 16.75 4.25 16.4142 4.25 16Z" fill="#22C55E" class="path-2" />
                                                <path d="M11 0H7C5.89543 0 5 0.895431 5 2C5 3.10457 5.89543 4 7 4H11C12.1046 4 13 3.10457 13 2C13 0.895431 12.1046 0 11 0Z" fill="#22C55E" class="path-2" />
                                            </svg>
                                        </span>
                                        <span class="item-text text-lg font-medium leading-none">Roles</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endif
                    {{-- <li class="item py-[11px] text-bgray-900 dark:text-white">
                        <a href="integrations.html">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <span class="item-ico">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1.57666 3.61499C1.57666 2.51042 2.47209 1.61499 3.57666 1.61499H8.5C9.60456 1.61499 10.5 2.51042 10.5 3.61499V8.53833C10.5 9.64289 9.60456 10.5383 8.49999 10.5383H3.57666C2.47209 10.5383 1.57666 9.64289 1.57666 8.53832V3.61499Z" fill="#1A202C" class="path-1" />
                                            <path d="M13.5 15.5383C13.5 14.4338 14.3954 13.5383 15.5 13.5383H20.4233C21.5279 13.5383 22.4233 14.4338 22.4233 15.5383V20.4617C22.4233 21.5662 21.5279 22.4617 20.4233 22.4617H15.5C14.3954 22.4617 13.5 21.5662 13.5 20.4617V15.5383Z" fill="#1A202C" class="path-1" />
                                            <circle cx="6.03832" cy="18" r="4.46166" fill="#1A202C" class="path-1" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M18 2C18.4142 2 18.75 2.33579 18.75 2.75V5.25H21.25C21.6642 5.25 22 5.58579 22 6C22 6.41421 21.6642 6.75 21.25 6.75H18.75V9.25C18.75 9.66421 18.4142 10 18 10C17.5858 10 17.25 9.66421 17.25 9.25V6.75H14.75C14.3358 6.75 14 6.41421 14 6C14 5.58579 14.3358 5.25 14.75 5.25H17.25V2.75C17.25 2.33579 17.5858 2 18 2Z" fill="#22C55E" class="path-2" />
                                        </svg>
                                    </span>
                                    <span class="item-text text-lg font-medium leading-none">Integrations</span>
                                </div>
                            </div>
                        </a>
                    </li> --}}
                    @if ($canViewUsers)
                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                            <a href="{{ route('users.index') }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5">
                                        <span class="item-ico">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <ellipse cx="11.7778" cy="17.5555" rx="7.77778" ry="4.44444" class="path-1" fill="#1A202C" />
                                                <circle class="path-2" cx="11.7778" cy="6.44444" r="4.44444" fill="#22C55E" />
                                            </svg>
                                        </span>
                                        <span class="item-text text-lg font-medium leading-none">Users</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endif
                    @if ($canViewTeams)
                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                            <a href="{{ route('teams.index') }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5">
                                        <span class="item-ico">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <ellipse cx="11.7778" cy="17.5555" rx="7.77778" ry="4.44444" class="path-1" fill="#1A202C" />
                                                <circle class="path-2" cx="11.7778" cy="6.44444" r="4.44444" fill="#22C55E" />
                                            </svg>
                                        </span>
                                        <span class="item-text text-lg font-medium leading-none">Teams</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endif
                    @if ($canViewCustomers)
                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                            <a href="{{ route('customers.index') }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5">
                                        <span class="item-ico">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <ellipse cx="11.7778" cy="17.5555" rx="7.77778" ry="4.44444" class="path-1" fill="#1A202C" />
                                                <circle class="path-2" cx="11.7778" cy="6.44444" r="4.44444" fill="#22C55E" />
                                            </svg>
                                        </span>
                                        <span class="item-text text-lg font-medium leading-none">Customers</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endif
                    {{-- <li class="item py-[11px] text-bgray-900 dark:text-white">
                        <a href="calender.html">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <span class="item-ico">
                                        <svg width="18" height="21" viewBox="0 0 18 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0 6.5C0 4.29086 1.79086 2.5 4 2.5H14C16.2091 2.5 18 4.29086 18 6.5V8V17C18 19.2091 16.2091 21 14 21H4C1.79086 21 0 19.2091 0 17V8V6.5Z" fill="#1A202C" class="path-1" />
                                            <path d="M14 2.5H4C1.79086 2.5 0 4.29086 0 6.5V8H18V6.5C18 4.29086 16.2091 2.5 14 2.5Z" fill="#22C55E" class="path-2" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5 0.25C5.41421 0.25 5.75 0.585786 5.75 1V4C5.75 4.41421 5.41421 4.75 5 4.75C4.58579 4.75 4.25 4.41421 4.25 4V1C4.25 0.585786 4.58579 0.25 5 0.25ZM13 0.25C13.4142 0.25 13.75 0.585786 13.75 1V4C13.75 4.41421 13.4142 4.75 13 4.75C12.5858 4.75 12.25 4.41421 12.25 4V1C12.25 0.585786 12.5858 0.25 13 0.25Z" fill="#1A202C" class="path-2" />
                                            <circle cx="9" cy="14" r="1" fill="#22C55E" />
                                            <circle cx="13" cy="14" r="1" fill="#22C55E" class="path-2" />
                                            <circle cx="5" cy="14" r="1" fill="#22C55E" class="path-2" />
                                        </svg>
                                    </span>
                                    <span class="item-text text-lg font-medium leading-none">Calender</span>
                                </div>
                            </div>
                        </a>
                    </li> --}}
                    {{-- <li class="item py-[11px] text-bgray-900 dark:text-white">
                        <a href="history.html">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <span class="item-ico">
                                        <svg width="18" height="21" viewBox="0 0 18 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M17.5 12.5C17.5 17.1944 13.6944 21 9 21C4.30558 21 0.5 17.1944 0.5 12.5C0.5 7.80558 4.30558 4 9 4C13.6944 4 17.5 7.80558 17.5 12.5Z" fill="#1A202C" class="path-1" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.99995 1.75C8.02962 1.75 7.09197 1.88462 6.20407 2.13575C5.80549 2.24849 5.39099 2.01676 5.27826 1.61818C5.16553 1.21961 5.39725 0.805108 5.79583 0.692376C6.81525 0.404046 7.89023 0.25 8.99995 0.25C10.1097 0.25 11.1846 0.404046 12.2041 0.692376C12.6026 0.805108 12.8344 1.21961 12.7216 1.61818C12.6089 2.01676 12.1944 2.24849 11.7958 2.13575C10.9079 1.88462 9.97028 1.75 8.99995 1.75Z" fill="#22C55E" class="path-2" />
                                            <path d="M11 13C11 14.1046 10.1046 15 9 15C7.89543 15 7 14.1046 7 13C7 11.8954 7.89543 11 9 11C10.1046 11 11 11.8954 11 13Z" fill="#22C55E" class="path-2" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M9 7.25C9.41421 7.25 9.75 7.58579 9.75 8V12C9.75 12.4142 9.41421 12.75 9 12.75C8.58579 12.75 8.25 12.4142 8.25 12V8C8.25 7.58579 8.58579 7.25 9 7.25Z" fill="#22C55E" class="path-2" />
                                        </svg>
                                    </span>
                                    <span class="item-text text-lg font-medium leading-none">History</span>
                                </div>
                            </div>
                        </a>
                    </li> --}}
                </ul>
            </div>
            @if ($hasWorkspaceLinks)
                <div class="item-wrapper mb-5">
                    <h4 class="border-b border-bgray-200 text-sm font-medium leading-7 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">
                        Group Name
                    </h4>
                    <ul class="mt-2.5">

                        @if ($canViewProjects)
                            <!-- Projects -->
                            <li class="item py-[11px] text-bgray-900 dark:text-white">
                                <a href="{{ route('projects.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="item-ico">
                                                <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5 2V11C5 12.1046 5.89543 13 7 13H18C19.1046 13 20 12.1046 20 11V2C20 0.895431 19.1046 0 18 0H7C5.89543 0 5 0.89543 5 2Z" fill="#1A202C" class="path-1" />
                                                    <path d="M0 15C0 13.8954 0.895431 13 2 13H2.17157C2.70201 13 3.21071 13.2107 3.58579 13.5858C4.36683 14.3668 5.63317 14.3668 6.41421 13.5858C6.78929 13.2107 7.29799 13 7.82843 13H8C9.10457 13 10 13.8954 10 15V16C10 17.1046 9.10457 18 8 18H2C0.89543 18 0 17.1046 0 16V15Z" fill="#22C55E" class="path-2" />
                                                    <path d="M7.5 9.5C7.5 10.8807 6.38071 12 5 12C3.61929 12 2.5 10.8807 2.5 9.5C2.5 8.11929 3.61929 7 5 7C6.38071 7 7.5 8.11929 7.5 9.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 4.5C8.25 4.08579 8.58579 3.75 9 3.75L16 3.75C16.4142 3.75 16.75 4.08579 16.75 4.5C16.75 4.91421 16.4142 5.25 16 5.25L9 5.25C8.58579 5.25 8.25 4.91421 8.25 4.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.25 8.5C11.25 8.08579 11.5858 7.75 12 7.75L16 7.75C16.4142 7.75 16.75 8.08579 16.75 8.5C16.75 8.91421 16.4142 9.25 16 9.25L12 9.25C11.5858 9.25 11.25 8.91421 11.25 8.5Z" fill="#22C55E" class="path-2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-lg font-medium leading-none">Projects</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewTasks)
                            <!-- Tasks -->
                            <li class="item py-[11px] text-bgray-900 dark:text-white">
                                <a href="{{ route('tasks.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="item-ico">
                                                <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5 2V11C5 12.1046 5.89543 13 7 13H18C19.1046 13 20 12.1046 20 11V2C20 0.895431 19.1046 0 18 0H7C5.89543 0 5 0.89543 5 2Z" fill="#1A202C" class="path-1" />
                                                    <path d="M0 15C0 13.8954 0.895431 13 2 13H2.17157C2.70201 13 3.21071 13.2107 3.58579 13.5858C4.36683 14.3668 5.63317 14.3668 6.41421 13.5858C6.78929 13.2107 7.29799 13 7.82843 13H8C9.10457 13 10 13.8954 10 15V16C10 17.1046 9.10457 18 8 18H2C0.89543 18 0 17.1046 0 16V15Z" fill="#22C55E" class="path-2" />
                                                    <path d="M7.5 9.5C7.5 10.8807 6.38071 12 5 12C3.61929 12 2.5 10.8807 2.5 9.5C2.5 8.11929 3.61929 7 5 7C6.38071 7 7.5 8.11929 7.5 9.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 4.5C8.25 4.08579 8.58579 3.75 9 3.75L16 3.75C16.4142 3.75 16.75 4.08579 16.75 4.5C16.75 4.91421 16.4142 5.25 16 5.25L9 5.25C8.58579 5.25 8.25 4.91421 8.25 4.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.25 8.5C11.25 8.08579 11.5858 7.75 12 7.75L16 7.75C16.4142 7.75 16.75 8.08579 16.75 8.5C16.75 8.91421 16.4142 9.25 16 9.25L12 9.25C11.5858 9.25 11.25 8.91421 11.25 8.5Z" fill="#22C55E" class="path-2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-lg font-medium leading-none">Tasks</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewTasks)
                            <!-- Kanban -->
                            <li class="item py-[11px] text-bgray-900 dark:text-white">
                                <a href="{{ route('tasks.kanban.view') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="item-ico">
                                                <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5 2V11C5 12.1046 5.89543 13 7 13H18C19.1046 13 20 12.1046 20 11V2C20 0.895431 19.1046 0 18 0H7C5.89543 0 5 0.89543 5 2Z" fill="#1A202C" class="path-1" />
                                                    <path d="M0 15C0 13.8954 0.895431 13 2 13H2.17157C2.70201 13 3.21071 13.2107 3.58579 13.5858C4.36683 14.3668 5.63317 14.3668 6.41421 13.5858C6.78929 13.2107 7.29799 13 7.82843 13H8C9.10457 13 10 13.8954 10 15V16C10 17.1046 9.10457 18 8 18H2C0.89543 18 0 17.1046 0 16V15Z" fill="#22C55E" class="path-2" />
                                                    <path d="M7.5 9.5C7.5 10.8807 6.38071 12 5 12C3.61929 12 2.5 10.8807 2.5 9.5C2.5 8.11929 3.61929 7 5 7C6.38071 7 7.5 8.11929 7.5 9.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 4.5C8.25 4.08579 8.58579 3.75 9 3.75L16 3.75C16.4142 3.75 16.75 4.08579 16.75 4.5C16.75 4.91421 16.4142 5.25 16 5.25L9 5.25C8.58579 5.25 8.25 4.91421 8.25 4.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.25 8.5C11.25 8.08579 11.5858 7.75 12 7.75L16 7.75C16.4142 7.75 16.75 8.08579 16.75 8.5C16.75 8.91421 16.4142 9.25 16 9.25L12 9.25C11.5858 9.25 11.25 8.91421 11.25 8.5Z" fill="#22C55E" class="path-2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-lg font-medium leading-none">Kanban</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewTaskRequests)
                            <!-- Task Requests -->
                            <li class="item py-[11px] text-bgray-900 dark:text-white">
                                <a href="{{ route('tasks.requests.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="item-ico">
                                                <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5 2V11C5 12.1046 5.89543 13 7 13H18C19.1046 13 20 12.1046 20 11V2C20 0.895431 19.1046 0 18 0H7C5.89543 0 5 0.89543 5 2Z" fill="#1A202C" class="path-1" />
                                                    <path d="M0 15C0 13.8954 0.895431 13 2 13H2.17157C2.70201 13 3.21071 13.2107 3.58579 13.5858C4.36683 14.3668 5.63317 14.3668 6.41421 13.5858C6.78929 13.2107 7.29799 13 7.82843 13H8C9.10457 13 10 13.8954 10 15V16C10 17.1046 9.10457 18 8 18H2C0.89543 18 0 17.1046 0 16V15Z" fill="#22C55E" class="path-2" />
                                                    <path d="M7.5 9.5C7.5 10.8807 6.38071 12 5 12C3.61929 12 2.5 10.8807 2.5 9.5C2.5 8.11929 3.61929 7 5 7C6.38071 7 7.5 8.11929 7.5 9.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 4.5C8.25 4.08579 8.58579 3.75 9 3.75L16 3.75C16.4142 3.75 16.75 4.08579 16.75 4.5C16.75 4.91421 16.4142 5.25 16 5.25L9 5.25C8.58579 5.25 8.25 4.91421 8.25 4.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.25 8.5C11.25 8.08579 11.5858 7.75 12 7.75L16 7.75C16.4142 7.75 16.75 8.08579 16.75 8.5C16.75 8.91421 16.4142 9.25 16 9.25L12 9.25C11.5858 9.25 11.25 8.91421 11.25 8.5Z" fill="#22C55E" class="path-2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-lg font-medium leading-none">Task Requests</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewTaskTimeLogChangeRequests)
                            <!-- Task Requests -->
                            <li class="item py-[11px] text-bgray-900 dark:text-white">
                                <a href="{{ route('tasks.time-log-change-requests.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="item-ico">
                                                <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5 2V11C5 12.1046 5.89543 13 7 13H18C19.1046 13 20 12.1046 20 11V2C20 0.895431 19.1046 0 18 0H7C5.89543 0 5 0.89543 5 2Z" fill="#1A202C" class="path-1" />
                                                    <path d="M0 15C0 13.8954 0.895431 13 2 13H2.17157C2.70201 13 3.21071 13.2107 3.58579 13.5858C4.36683 14.3668 5.63317 14.3668 6.41421 13.5858C6.78929 13.2107 7.29799 13 7.82843 13H8C9.10457 13 10 13.8954 10 15V16C10 17.1046 9.10457 18 8 18H2C0.89543 18 0 17.1046 0 16V15Z" fill="#22C55E" class="path-2" />
                                                    <path d="M7.5 9.5C7.5 10.8807 6.38071 12 5 12C3.61929 12 2.5 10.8807 2.5 9.5C2.5 8.11929 3.61929 7 5 7C6.38071 7 7.5 8.11929 7.5 9.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 4.5C8.25 4.08579 8.58579 3.75 9 3.75L16 3.75C16.4142 3.75 16.75 4.08579 16.75 4.5C16.75 4.91421 16.4142 5.25 16 5.25L9 5.25C8.58579 5.25 8.25 4.91421 8.25 4.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.25 8.5C11.25 8.08579 11.5858 7.75 12 7.75L16 7.75C16.4142 7.75 16.75 8.08579 16.75 8.5C16.75 8.91421 16.4142 9.25 16 9.25L12 9.25C11.5858 9.25 11.25 8.91421 11.25 8.5Z" fill="#22C55E" class="path-2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-lg font-medium leading-none">Time Requests</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
            @if ($canViewReports)
                <div class="item-wrapper mb-5">
                    <h4 class="border-b border-bgray-200 text-sm font-medium leading-7 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">
                        Reports
                    </h4>

                    <ul class="mt-2.5 space-y-1">
                        {{-- ================= PERFORMANCE ================= --}}
                        @if ($canViewProductivityReports || $canViewTimeTrackingReports || $canViewDailyReports)
                            <li x-data="{ open: false }">

                                <div @click="open = !open"
                                    class="flex items-center justify-between cursor-pointer py-2">
                                    <span class="item-text text-lg font-medium leading-none">Performance</span>
                                    <span>▾</span>
                                </div>

                                <ul x-show="open" class="pl-4 space-y-1">

                                    @if ($canViewProductivityReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="http://127.0.0.1:8000/schedule-shift">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none">
                                                                <path d="M2 14l4-4 3 3 6-7 3 2" stroke="#22C55E" stroke-width="2"/>
                                                                <circle cx="2" cy="14" r="2" fill="#1A202C"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Productivity Report</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewTimeTrackingReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="http://127.0.0.1:8000/schedule-shift">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none">
                                                                <circle cx="10" cy="9" r="7" fill="#1A202C"/>
                                                                <path d="M10 5v4l3 2" stroke="#22C55E" stroke-width="2"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Time Tracking Report</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewDailyReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="http://127.0.0.1:8000/schedule-shift">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none">
                                                                <rect x="2" y="3" width="16" height="13" rx="2" fill="#1A202C"/>
                                                                <path d="M2 7h16" stroke="#22C55E" stroke-width="2"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Daily Report</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                </ul>
                            </li>
                        @endif

                        {{-- ================= RESOURCES ================= --}}
                        @if($canViewAttendanceReports || $canViewLeaveReports || $canViewShiftScheduleReports)
                            <li x-data="{ open: false }">

                                <div @click="open = !open"
                                    class="flex items-center justify-between cursor-pointer py-2">
                                    <span class="item-text text-lg font-medium leading-none">Resources</span>
                                    <span>▾</span>
                                </div>

                                <ul x-show="open" class="pl-4 space-y-1">

                                    @if ($canViewAttendanceReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="http://127.0.0.1:8000/schedule-shift">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none">
                                                                <circle cx="7" cy="6" r="3" fill="#1A202C"/>
                                                                <circle cx="14" cy="6" r="3" fill="#1A202C"/>
                                                                <path d="M3 16c1.5-3 12.5-3 14 0" stroke="#22C55E" stroke-width="2"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Attendance Report</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewLeaveReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="http://127.0.0.1:8000/schedule-shift">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none">
                                                                <circle cx="10" cy="6" r="3" fill="#1A202C"/>
                                                                <path d="M4 16c1-3 11-3 12 0" stroke="#22C55E" stroke-width="2"/>
                                                                <path d="M6 2l8 14" stroke="#22C55E" stroke-width="2"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Leave Report</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewShiftScheduleReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="http://127.0.0.1:8000/schedule-shift">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none">
                                                                <rect x="2" y="3" width="16" height="12" rx="2" fill="#1A202C"/>
                                                                <path d="M5 6h4M5 10h8M5 14h6" stroke="#22C55E" stroke-width="2"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Shift Schedule Report</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                </ul>
                            </li>
                        @endif

                        {{-- ================= PROJECTS ================= --}}

                        @if($canViewProjectReports || $canViewMilestoneReports || $canViewSprintReports || $canViewTaskReports)
                            <li x-data="{ open: false }">

                                <div @click="open = !open"
                                    class="flex items-center justify-between cursor-pointer py-2">
                                    <span class="item-text text-lg font-medium leading-none">Projects</span>
                                    <span>▾</span>
                                </div>

                                <ul x-show="open" class="pl-4 space-y-1">

                                    @if ($canViewProjectReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="{{ route('projects.report') }}">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <rect x="1" y="2" width="18" height="14" rx="2" fill="#1A202C"/>
                                                                <rect x="3" y="5" width="6" height="2" fill="#22C55E"/>
                                                                <rect x="3" y="9" width="10" height="2" fill="#22C55E"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Project Report</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewMilestoneReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="http://127.0.0.1:8000/schedule-shift">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none">
                                                                <path d="M4 2v14" stroke="#22C55E" stroke-width="2"/>
                                                                <path d="M4 3h10l-2 3 2 3H4" fill="#1A202C"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Milestone Report</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewSprintReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="http://127.0.0.1:8000/schedule-shift">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none">
                                                                <path d="M3 14c4-10 10-10 14 0" stroke="#22C55E" stroke-width="2"/>
                                                                <circle cx="10" cy="9" r="2" fill="#1A202C"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Sprint Report</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canViewTaskReports)
                                        <li class="item py-[11px] text-bgray-900 dark:text-white">
                                            <a href="{{ route('tasks.report') }}">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <span class="item-ico">
                                                            <svg width="20" height="18" viewBox="0 0 20 18" fill="none">
                                                                <path d="M3 2h14v14H3V2z" fill="#1A202C"/>
                                                                <path d="M5 6l2 2 3-3" stroke="#22C55E" stroke-width="2" fill="none"/>
                                                                <path d="M5 10l2 2 3-3" stroke="#22C55E" stroke-width="2" fill="none"/>
                                                            </svg>
                                                        </span>
                                                        <span class="item-text text-lg font-medium leading-none">Task Report</span>
                                                    </div>
                                                </div>
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
                    <h4 class="border-b border-bgray-200 text-sm font-medium leading-7 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">
                        Configurations
                    </h4>
                    <ul class="mt-2.5">

                        @if ($canViewScheduleShift)
                            <li class="item py-[11px] text-bgray-900 dark:text-white">
                                <a href="{{ route('schedule.shift.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="item-ico">
                                                <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5 2V11C5 12.1046 5.89543 13 7 13H18C19.1046 13 20 12.1046 20 11V2C20 0.895431 19.1046 0 18 0H7C5.89543 0 5 0.89543 5 2Z" fill="#1A202C" class="path-1" />
                                                    <path d="M0 15C0 13.8954 0.895431 13 2 13H2.17157C2.70201 13 3.21071 13.2107 3.58579 13.5858C4.36683 14.3668 5.63317 14.3668 6.41421 13.5858C6.78929 13.2107 7.29799 13 7.82843 13H8C9.10457 13 10 13.8954 10 15V16C10 17.1046 9.10457 18 8 18H2C0.89543 18 0 17.1046 0 16V15Z" fill="#22C55E" class="path-2" />
                                                    <path d="M7.5 9.5C7.5 10.8807 6.38071 12 5 12C3.61929 12 2.5 10.8807 2.5 9.5C2.5 8.11929 3.61929 7 5 7C6.38071 7 7.5 8.11929 7.5 9.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 4.5C8.25 4.08579 8.58579 3.75 9 3.75L16 3.75C16.4142 3.75 16.75 4.08579 16.75 4.5C16.75 4.91421 16.4142 5.25 16 5.25L9 5.25C8.58579 5.25 8.25 4.91421 8.25 4.5Z" fill="#22C55E" class="path-2" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.25 8.5C11.25 8.08579 11.5858 7.75 12 7.75L16 7.75C16.4142 7.75 16.75 8.08579 16.75 8.5C16.75 8.91421 16.4142 9.25 16 9.25L12 9.25C11.5858 9.25 11.25 8.91421 11.25 8.5Z" fill="#22C55E" class="path-2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-lg font-medium leading-none">Schedule Shift</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewSettings)
                            <li class="item py-[11px] text-bgray-900 dark:text-white">
                                <a href="{{ route('settings.index') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="item-ico">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M13.0606 2H10.9394C9.76787 2 8.81817 2.89543 8.81817 4C8.81817 5.26401 7.46574 6.06763 6.35556 5.4633L6.24279 5.40192C5.22823 4.84963 3.93091 5.17738 3.34515 6.13397L2.28455 7.86602C1.69879 8.8226 2.0464 10.0458 3.06097 10.5981C4.17168 11.2027 4.17168 12.7973 3.06096 13.4019C2.0464 13.9542 1.69879 15.1774 2.28454 16.134L3.34515 17.866C3.93091 18.8226 5.22823 19.1504 6.24279 18.5981L6.35555 18.5367C7.46574 17.9324 8.81817 18.736 8.81817 20C8.81817 21.1046 9.76787 22 10.9394 22H13.0606C14.2321 22 15.1818 21.1046 15.1818 20C15.1818 18.736 16.5343 17.9324 17.6445 18.5367L17.7572 18.5981C18.7718 19.1504 20.0691 18.8226 20.6548 17.866L21.7155 16.134C22.3012 15.1774 21.9536 13.9542 20.939 13.4019C19.8283 12.7973 19.8283 11.2027 20.939 10.5981C21.9536 10.0458 22.3012 8.82262 21.7155 7.86603L20.6548 6.13398C20.0691 5.1774 18.7718 4.84965 17.7572 5.40193L17.6445 5.46331C16.5343 6.06765 15.1818 5.26402 15.1818 4C15.1818 2.89543 14.2321 2 13.0606 2Z"
                                                        fill="#1A202C" class="path-1" />
                                                    <path d="M15.75 12C15.75 14.0711 14.0711 15.75 12 15.75C9.92893 15.75 8.25 14.0711 8.25 12C8.25 9.92893 9.92893 8.25 12 8.25C14.0711 8.25 15.75 9.92893 15.75 12Z" fill="#22C55E" class="path-2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-lg font-medium leading-none">Settings</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if ($canViewActivityLog)
                            <li class="item py-[11px] text-bgray-900 dark:text-white">
                                <a href="{{ route('activity.log') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="item-ico">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M13.0606 2H10.9394C9.76787 2 8.81817 2.89543 8.81817 4C8.81817 5.26401 7.46574 6.06763 6.35556 5.4633L6.24279 5.40192C5.22823 4.84963 3.93091 5.17738 3.34515 6.13397L2.28455 7.86602C1.69879 8.8226 2.0464 10.0458 3.06097 10.5981C4.17168 11.2027 4.17168 12.7973 3.06096 13.4019C2.0464 13.9542 1.69879 15.1774 2.28454 16.134L3.34515 17.866C3.93091 18.8226 5.22823 19.1504 6.24279 18.5981L6.35555 18.5367C7.46574 17.9324 8.81817 18.736 8.81817 20C8.81817 21.1046 9.76787 22 10.9394 22H13.0606C14.2321 22 15.1818 21.1046 15.1818 20C15.1818 18.736 16.5343 17.9324 17.6445 18.5367L17.7572 18.5981C18.7718 19.1504 20.0691 18.8226 20.6548 17.866L21.7155 16.134C22.3012 15.1774 21.9536 13.9542 20.939 13.4019C19.8283 12.7973 19.8283 11.2027 20.939 10.5981C21.9536 10.0458 22.3012 8.82262 21.7155 7.86603L20.6548 6.13398C20.0691 5.1774 18.7718 4.84965 17.7572 5.40193L17.6445 5.46331C16.5343 6.06765 15.1818 5.26402 15.1818 4C15.1818 2.89543 14.2321 2 13.0606 2Z"
                                                        fill="#1A202C" class="path-1" />
                                                    <path d="M15.75 12C15.75 14.0711 14.0711 15.75 12 15.75C9.92893 15.75 8.25 14.0711 8.25 12C8.25 9.92893 9.92893 8.25 12 8.25C14.0711 8.25 15.75 9.92893 15.75 12Z" fill="#22C55E" class="path-2" />
                                                </svg>
                                            </span>
                                            <span class="item-text text-lg font-medium leading-none">Activity Log</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif

                    </ul>
                </div>
            @endif
            <div class="item-wrapper mb-5">
                <h4 class="border-b border-bgray-200 text-sm font-medium leading-7 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">

                </h4>
                <ul class="mt-2.5">
                    {{-- <li class="item py-[11px] text-bgray-900 dark:text-white">
                        <a href="signin.html">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <span class="item-ico">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <ellipse cx="11.7778" cy="17.5555" rx="7.77778" ry="4.44444" fill="#1A202C" class="path-1" />
                                            <circle cx="11.7778" cy="6.44444" r="4.44444" fill="#22C55E" class="path-2" />
                                        </svg>
                                    </span>
                                    <span class="item-text text-lg font-medium leading-none">Signin</span>
                                </div>
                            </div>
                        </a>
                    </li> --}}
                    {{-- <li class="item py-[11px] text-bgray-900 dark:text-white">
                        <a href="signup.html">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <span class="item-ico">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <ellipse cx="11.7778" cy="17.5555" rx="7.77778" ry="4.44444" fill="#1A202C" class="path-1" />
                                            <circle cx="11.7778" cy="6.44444" r="4.44444" fill="#22C55E" class="path-2" />
                                        </svg>
                                    </span>
                                    <span class="item-text text-lg font-medium leading-none">Signup</span>
                                </div>
                            </div>
                        </a>
                    </li> --}}
                    {{-- <li class="item py-[11px] text-bgray-900 dark:text-white">
                        <a href="coming-soon.html">
                            <div class="flex items-center space-x-2.5">
                                <span class="item-ico">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18.4 17.2C19.8833 19.1777 18.4721 22 16 22L8 22C5.52786 22 4.11672 19.1777 5.6 17.2L8.15 13.8C8.95 12.7333 8.95 11.2667 8.15 10.2L5.6 6.8C4.11672 4.82229 5.52787 2 8 2L16 2C18.4721 2 19.8833 4.82229 18.4 6.8L15.85 10.2C15.05 11.2667 15.05 12.7333 15.85 13.8L18.4 17.2Z" fill="#1A202C" class="path-1" />
                                        <path d="M12.7809 9.02391C12.3805 9.52432 11.6195 9.52432 11.2191 9.02391L9.29976 6.6247C8.77595 5.96993 9.24212 5 10.0806 5L13.9194 5C14.7579 5 15.2241 5.96993 14.7002 6.6247L12.7809 9.02391Z" fill="#22C55E" class="path-2" />
                                    </svg>
                                </span>
                                <span class="item-text text-lg font-medium leading-none">Coming Soon</span>
                            </div>
                        </a>
                    </li> --}}
                    {{-- <li class="item py-[11px] text-bgray-900 dark:text-white">
                        <a href="404.html">
                            <div class="flex items-center space-x-2.5">
                                <span class="item-ico">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="10" cy="10" r="10" fill="#1A202C" class="path-1" />
                                        <path d="M9 15C9 14.4477 9.44772 14 10 14C10.5523 14 11 14.4477 11 15C11 15.5523 10.5523 16 10 16C9.44772 16 9 15.5523 9 15Z" fill="#22C55E" class="path-2" />
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10 12.75C9.58579 12.75 9.25 12.4142 9.25 12L9.25 5C9.25 4.58579 9.58579 4.25 10 4.25C10.4142 4.25 10.75 4.58579 10.75 5L10.75 12C10.75 12.4142 10.4142 12.75 10 12.75Z" fill="#22C55E" class="path-2" />
                                    </svg>
                                </span>
                                <span class="item-text text-lg font-medium leading-none">404</span>
                            </div>
                        </a>
                    </li> --}}
                    <li class="item py-[11px] text-bgray-900 dark:text-white">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5">
                                        <span class="item-ico">
                                            <svg width="21" height="18" viewBox="0 0 21 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M17.1464 10.4394C16.8536 10.7323 16.8536 11.2072 17.1464 11.5001C17.4393 11.7929 17.9142 11.7929 18.2071 11.5001L19.5 10.2072C20.1834 9.52375 20.1834 8.41571 19.5 7.73229L18.2071 6.4394C17.9142 6.1465 17.4393 6.1465 17.1464 6.4394C16.8536 6.73229 16.8536 7.20716 17.1464 7.50006L17.8661 8.21973H11.75C11.3358 8.21973 11 8.55551 11 8.96973C11 9.38394 11.3358 9.71973 11.75 9.71973H17.8661L17.1464 10.4394Z" fill="#22C55E" class="path-2" />
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4.75 17.75H12C14.6234 17.75 16.75 15.6234 16.75 13C16.75 12.5858 16.4142 12.25 16 12.25C15.5858 12.25 15.25 12.5858 15.25 13C15.25 14.7949 13.7949 16.25 12 16.25H8.21412C7.34758 17.1733 6.11614 17.75 4.75 17.75ZM8.21412 1.75H12C13.7949 1.75 15.25 3.20507 15.25 5C15.25 5.41421 15.5858 5.75 16 5.75C16.4142 5.75 16.75 5.41421 16.75 5C16.75 2.37665 14.6234 0.25 12 0.25H4.75C6.11614 0.25 7.34758 0.82673 8.21412 1.75Z" fill="#1A202C" class="path-1" />
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M0 5C0 2.37665 2.12665 0.25 4.75 0.25C7.37335 0.25 9.5 2.37665 9.5 5V13C9.5 15.6234 7.37335 17.75 4.75 17.75C2.12665 17.75 0 15.6234 0 13V5Z" fill="#1A202C" class="path-1" />
                                            </svg>
                                        </span>
                                        <span class="item-text text-lg font-medium leading-none">
                                            Logout
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
        <div class="copy-write-text">
            <p class="text-sm text-[#969BA0]">© {{ date('Y') }} All Rights Reserved</p>
            <p class="text-sm font-medium text-bgray-700">
                <a href="https://www.tomsher.com/" target="_blank" class="border-b font-semibold hover:text-blue-600">Tomsher Technologies LLC</a>
            </p>
        </div>
    </div>
</aside>

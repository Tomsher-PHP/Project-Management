<!--page-title-->
<div class="flex items-center gap-3">
    <div class="space-y-1">
        <h3 class="text-lg font-bold leading-tight text-bgray-900 dark:text-bgray-50 lg:text-[28px]">
            {{ $pageTitle ?? 'Dashboard' }}
        </h3>
    </div>

    <!-- User dropdown in Workspace page -->
    @if (request()->routeIs('user.workspace') && $workspaceSelectableUsers->isNotEmpty())
        <div class="min-w-[240px] max-w-[320px]" data-workspace-user-select-root>
            <label for="workspace-user-select" class="sr-only">Workspace user</label>
            <select id="workspace-user-select" class="tom-select-no-search w-full" data-workspace-user-select>
                <option value="" @selected($workspaceSelectedUserId === '')>Select Users</option>
                @foreach ($workspaceSelectableUsers as $workspaceSelectableUser)
                    <option value="{{ $workspaceSelectableUser->id }}" @selected($workspaceSelectedUserId === (string) $workspaceSelectableUser->id)>
                        {{ $workspaceSelectableUser->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <!-- Pending Handoff requests in Workspace page -->
    @if (request()->routeIs('user.workspace') && ($pendingHandoffCount ?? 0) > 0)
        <a href="{{ route('handoff_requests.index') }}" class="group inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold transition-all duration-200 dark:text-white" title="View pending handoff requests">

            <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-amber-100 dark:ring-darkblack-600"></span>
            </span>

            <span class="whitespace-nowrap">
                You have
                <span class="font-bold">{{ $pendingHandoffCount }}</span>
                {{ \Illuminate\Support\Str::plural('handoff', $pendingHandoffCount) }}
            </span>

            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    @endif

    @if (request()->routeIs('project.analytics'))
        <div class="flex items-center gap-3" data-project-analytics-filters>
            <!-- User Filter -->
            <div class="min-w-[200px] max-w-[260px]" data-project-analytics-user-select-root>
                <label for="project-analytics-user-select" class="sr-only">User</label>
                <select id="project-analytics-user-select" class="tom-select-no-search w-full" data-project-analytics-user-select>
                    <option value="" @selected(empty($projectAnalyticsSelectedUserId))>All Users</option>
                    @foreach ($projectSelectableUsers ?? [] as $selectableUser)
                        <option value="{{ $selectableUser->id }}" @selected(($projectAnalyticsSelectedUserId ?? '') == (string) $selectableUser->id)>
                            {{ $selectableUser->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Project Filter (Lazy Loaded) -->
            <div class="min-w-[240px] max-w-[320px]" data-project-analytics-project-select-root>
                <label for="project-analytics-project-select" class="sr-only">Project</label>
                <select id="project-analytics-project-select" class="tom-select-lazy w-full" data-route="{{ route('project.analytics.search-projects') }}" data-sort="0" data-project-analytics-project-select data-placeholder="All Projects" data-allow-empty-option="true" @if (!empty($projectAnalyticsSelectedUserId)) data-user-id="{{ $projectAnalyticsSelectedUserId }}" @endif>
                    <option value="" @selected(empty($projectAnalyticsSelectedProjectId))>All Projects</option>
                    @if (!empty($selectedProject))
                        <option value="{{ $selectedProject->id }}" selected>
                            {{ $selectedProject->name }}
                        </option>
                    @endif
                </select>
            </div>
        </div>
    @endif

    @auth
        @php
            $broadcastService = app(\App\Services\BroadcastService::class);
            $navbarBroadcastCount = $broadcastService->getEligibleCountForUser(auth()->user());
            $navbarBirthdays = $broadcastService->getTodayAndUpcomingBirthdays(auth()->user(), \App\Models\Broadcast::BDAY_DELAY_DAYS);
            $navbarTodayBirthdayCount = $navbarBirthdays['todayBirthdays']->count();
            $navbarUpcomingBirthdayCount = $navbarBirthdays['upcomingBirthdays']->count();
            $navbarWorkAnniversaryDelayDays = defined(\App\Models\Broadcast::class . '::WORK_ANNIVERSARY_DELAY_DAYS')
                ? max(0, (int) \App\Models\Broadcast::WORK_ANNIVERSARY_DELAY_DAYS)
                : 0;
            $navbarWorkAnniversaries = $broadcastService->getTodayAndUpcomingWorkAnniversaries(auth()->user(), $navbarWorkAnniversaryDelayDays);
            $navbarTodayWorkAnniversaryCount = $navbarWorkAnniversaries['todayWorkAnniversaries']->count();
            $navbarUpcomingWorkAnniversaryCount = $navbarWorkAnniversaries['upcomingWorkAnniversaries']->count();
            $navbarTotalCount = $navbarBroadcastCount
                + $navbarTodayBirthdayCount
                + $navbarUpcomingBirthdayCount
                + $navbarTodayWorkAnniversaryCount
                + $navbarUpcomingWorkAnniversaryCount;
            $navbarHasTodayBirthday = $navbarTodayBirthdayCount > 0;
            $navbarHasTodayWorkAnniversary = $navbarTodayWorkAnniversaryCount > 0;
            $navbarShowAnniversaryIcon = ! $navbarHasTodayBirthday && $navbarHasTodayWorkAnniversary;
            $navbarDrawerTitle = match (true) {
                $navbarHasTodayBirthday && $navbarHasTodayWorkAnniversary => "Today's Birthday, Work Anniversary & Broadcasts",
                $navbarHasTodayBirthday => "Today's Birthday & Broadcasts",
                $navbarHasTodayWorkAnniversary => "Today's Work Anniversary & Broadcasts",
                default => 'View Broadcasts',
            };
        @endphp

        <!-- Global Broadcast Drawer Trigger -->
        <button type="button" id="broadcast-drawer-toggle-btn" data-broadcast-drawer-toggle class="{{ $navbarTotalCount > 0 ? 'inline-flex' : 'hidden' }} group items-center gap-2 px-3.5 py-2 text-xs font-semibold text-amber-800 transition-all duration-200 dark:text-white" title="{{ $navbarDrawerTitle }}" aria-label="Open Broadcasts Drawer">

            <span class="inline-flex items-center justify-center transition-transform duration-200 group-hover:scale-110" data-broadcast-icon-container>
                <!-- Birthday Icon (Shown if today has any user birthday) -->
                <svg data-icon="birthday" class="{{ $navbarHasTodayBirthday ? '' : 'hidden' }} text-pink-500 dark:text-pink-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8" />
                    <path d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1" />
                    <path d="M2 21h20" />
                    <path d="M7 8v3" />
                    <path d="M12 8v3" />
                    <path d="M17 8v3" />
                    <path d="M7 4h.01" />
                    <path d="M12 4h.01" />
                    <path d="M17 4h.01" />
                </svg>

                <!-- Work Anniversary Icon (Shown if today has a work anniversary and no birthday) -->
                <svg data-icon="anniversary" class="{{ $navbarShowAnniversaryIcon ? '' : 'hidden' }} text-amber-500 dark:text-amber-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="6" />
                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                </svg>

                <!-- Broadcast Icon (Shown if no today birthday or work anniversary) -->
                <svg data-icon="broadcast" class="{{ $navbarHasTodayBirthday || $navbarHasTodayWorkAnniversary ? 'hidden' : '' }} text-success-300 dark:text-success-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 11v2a2 2 0 002 2h2l4 4V5L7 9H5a2 2 0 00-2 2z" />
                    <path d="M15 9a4 4 0 010 6M18 6a8 8 0 010 12" />
                </svg>
            </span>

            <span id="navbar-broadcast-count-text" data-broadcast-count-text class="whitespace-nowrap font-semibold transition-all duration-200 text-bgray-900 dark:text-white">
                <span data-broadcast-count-number>{{ $navbarTotalCount }}</span> <span data-broadcast-count-label>{{ \Illuminate\Support\Str::plural('Broadcast', $navbarTotalCount) }}</span>
            </span>

            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    @endauth
</div>

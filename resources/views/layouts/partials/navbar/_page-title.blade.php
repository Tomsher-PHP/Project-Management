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
        <!-- Global Broadcast Drawer Trigger -->
        <button type="button" id="broadcast-drawer-toggle-btn" data-broadcast-drawer-toggle class="hidden group items-center gap-2 px-3.5 py-2 text-xs font-semibold text-amber-800 transition-all duration-200 dark:text-white" title="View Broadcasts" aria-label="Open Broadcasts Drawer">
            <span class="inline-flex items-center justify-center text-success-300 transition-transform duration-200 group-hover:scale-110">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 11v2a2 2 0 002 2h2l4 4V5L7 9H5a2 2 0 00-2 2z" />
                    <path d="M15 9a4 4 0 010 6M18 6a8 8 0 010 12" />
                </svg>
            </span>
            <span id="navbar-broadcast-count-text" data-broadcast-count-text class="whitespace-nowrap font-semibold transition-all duration-200 text-bgray-900 dark:text-white">
                <span data-broadcast-count-number>0</span> <span data-broadcast-count-label>Broadcasts</span>
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    @endauth
</div>

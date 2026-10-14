<!--page-title-->
<div class="flex items-center gap-3">
    <div class="space-y-1">
        <h3 class="text-lg font-bold leading-tight text-bgray-900 dark:text-bgray-50 lg:text-[28px]">
            {{ $pageTitle ?? 'Dashboard' }}
        </h3>
    </div>

    @if ((request()->routeIs('user.workspace') || request()->routeIs('user.analytics')) && $workspaceSelectableUsers->isNotEmpty())
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


    @if ((request()->routeIs('user.workspace') || request()->routeIs('user.analytics')) && ($pendingHandoffCount ?? 0) > 0)

<a
    href="{{ route('handoff_requests.index') }}"
    class="group inline-flex items-center gap-2.5  bg-amber-50 px-3.5 py-2 text-xs font-semibold text-amber-800 transition-all duration-200 hover:border-amber-300 hover:bg-amber-100 hover:shadow-md dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
    title="View pending handoff requests"
>

            <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-500 opacity-60"></span>
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
                <select id="project-analytics-project-select"
                    class="tom-select-lazy w-full"
                    data-route="{{ route('project.analytics.search-projects') }}"
                    data-sort="0"
                    data-project-analytics-project-select
                    data-placeholder="All Projects"
                    data-allow-empty-option="true"
                    @if(!empty($projectAnalyticsSelectedUserId)) data-user-id="{{ $projectAnalyticsSelectedUserId }}" @endif>
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
</div>

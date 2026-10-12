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

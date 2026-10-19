<!-- Project Hosting Tab -->
@can('project_hosting.view')
    <div class="space-y-6" data-project-hosting-root>
        {{-- Summary Cards --}}
        @include('projects.partials.tabs.hosting.summary-cards', [
            'project' => $project,
            'summaryMetrics' => $summaryMetrics,
        ])

        {{-- Main Hosting Section --}}
        <section class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                        Server & Hosting
                    </h4>
                    <p class="text-xs text-bgray-700 dark:text-bgray-300">
                        Manage project hosting services, providers, billing, and renewal dates.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @can('project_hosting.create')
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400" data-project-hosting-modal-open>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Hosting</span>
                        </button>
                    @endcan
                </div>
            </div>

            <!-- Search & Filters Toolbar -->
            @include('projects.partials.tabs.hosting.filters', [
                'project' => $project,
                'filterOptions' => $filterOptions,
            ])

            <!-- Records List & Pagination Container -->
            <div id="project-hosting-history" data-project-hosting-container data-index-url="{{ route('projects.hosting.index', $project) }}" class="relative min-h-[160px]">
                @include('projects.partials.tabs.hosting.list', [
                    'project' => $project,
                    'hostingRecords' => $hostingRecords,
                ])
            </div>
        </section>

        @if(auth()->user()?->can('project_hosting.create') || auth()->user()?->can('project_hosting.edit'))
            @include('projects.partials.tabs.hosting.form-modal', [
                'project' => $project,
                'formOptions' => $formOptions,
            ])
        @endif

        @include('projects.partials.tabs.hosting.view-modal', [
            'project' => $project,
        ])

        @can('project_hosting.edit')
            @include('projects.partials.tabs.hosting.renewal-modal', [
                'project' => $project,
                'formOptions' => $formOptions,
            ])
        @endcan
    </div>
@endcan

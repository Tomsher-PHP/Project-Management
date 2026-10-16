<!-- Project Hosting Tab -->
@can('project_hosting.view')
    <div class="space-y-6" data-project-hosting-root>
        <section class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                        Hosting & Server Management
                    </h4>
                    <p class="text-xs text-bgray-700 dark:text-bgray-300">
                        Manage servers, cloud services, domains, and hosting configurations for this project.
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

            <div id="project-hosting-history" data-project-hosting-container data-index-url="{{ route('projects.hosting.index', $project) }}">
                @include('projects.partials.tabs.hosting.list', [
                    'project' => $project,
                    'hostingRecords' => $hostingRecords,
                ])
            </div>
        </section>

        @can('project_hosting.create')
            @include('projects.partials.tabs.hosting.form-modal', [
                'project' => $project,
                'formOptions' => $formOptions,
            ])
        @endcan

        @include('projects.partials.tabs.hosting.view-modal', [
            'project' => $project,
        ])
    </div>
@endcan

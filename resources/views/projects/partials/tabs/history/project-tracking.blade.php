<!-- Project Tracking -->
@can('project_tracking.view')
    <section class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
            <div>
                <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                    Project Tracking
                </h4>
            </div>
            <div class="flex gap-1">
                @can('project_tracking.create')
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400" data-project-tracking-modal-open>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>

                        <span>Add Tracking</span>
                    </button>
                @endcan

                @can('project_tracking.export')
                    <button type="button" id="bulk-export-project-trackings" data-url="{{ route('projects.trackings.bulk-export', $project) }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50" disabled>
                        <i class="fa-solid fa-file-export"></i>
                        <span>Export</span>
                    </button>
                @endcan
            </div>
        </div>

        <div id="project-tracking-history">
            @include('projects.partials.project-trackings.show', [
                'projectTrackings' => $projectTrackings,
            ])
        </div>
    </section>
@endcan

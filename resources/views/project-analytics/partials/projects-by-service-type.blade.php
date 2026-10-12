<div class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-project-analytics-section="service-type" data-url="{{ route('project.analytics.projects-by-service-type') }}">
    <div class="flex items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <div>
            <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Projects by Service Type</h4>
            <p class="text-xs text-bgray-500 dark:text-bgray-400">Distribution of projects across service types</p>
        </div>
        <div class="text-xs font-semibold text-bgray-600 dark:text-bgray-300">
            Total: <span data-service-type-total-count class="font-bold text-bgray-900 dark:text-white">—</span>
        </div>
    </div>
    <div class="flex min-h-[320px] flex-col p-5">
        <!-- Loading State Skeleton -->
        <div data-loading-state class="space-y-4">
            @for ($i = 0; $i < 4; $i++)
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <div class="h-3.5 w-32 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                        <div class="h-3.5 w-12 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    </div>
                    <div class="h-2 w-full animate-pulse rounded-full bg-bgray-200 dark:bg-darkblack-500"></div>
                </div>
            @endfor
        </div>

        <!-- Empty State -->
        <div data-empty-state class="hidden flex h-full flex-1 flex-col items-center justify-center text-center py-8">
            <div class="mb-3">
                <img src="{{ asset(config('assets.images.chart_no_data')) }}" alt="No data" class="mx-auto h-28 w-auto opacity-80">
            </div>
            <h4 class="text-sm font-semibold text-bgray-900 dark:text-white">No service type data</h4>
            <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-400">
                No projects found for the selected filters.
            </p>
        </div>

        <!-- Content List -->
        <div data-content class="hidden space-y-4 max-h-[380px] overflow-y-auto pr-1" data-service-type-list>
            <!-- Rendered dynamically by JS -->
        </div>
    </div>
</div>

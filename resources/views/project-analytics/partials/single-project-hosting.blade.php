<div class="hidden overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600"
     data-single-project-hosting-section
     data-url="{{ route('project.analytics.project-hosting') }}">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <div>
            <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Server & Hosting</h4>
            <p class="text-xs text-bgray-700 dark:text-bgray-300">Hosting and server infrastructure for this project</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300" data-single-project-hosting-count-badge>
                <span data-single-project-hosting-count>0</span> records
            </span>
        </div>
    </div>

    <!-- Body -->
    <div class="p-5">
        <!-- Loading State Skeleton -->
        <div data-loading-state class="space-y-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="flex items-center justify-between gap-4 py-2">
                    <div class="h-4 w-1/5 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/5 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/6 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/6 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/6 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/12 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                </div>
            @endfor
        </div>

        <!-- Content Container (AJAX rendered projects.partials.tabs.hosting.list) -->
        <div data-content class="hidden [&_table_tr>*:last-child]:hidden" data-single-project-hosting-content>
            <!-- Injected dynamically via JS -->
        </div>

        <!-- Empty State (Fallback if list view doesn't render its own empty state) -->
        <div data-empty-state class="hidden flex h-full flex-col items-center justify-center text-center py-8">
            <div class="mb-3">
                <img src="{{ asset(config('assets.images.chart_no_data')) }}" alt="No hosting records" class="mx-auto h-28 w-auto opacity-80">
            </div>
            <h4 class="text-sm font-semibold text-bgray-900 dark:text-white">No hosting records found</h4>
            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">
                No hosting records recorded for this project yet.
            </p>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-[10px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-single-project-time-progress-section>
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/70 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <div>
            <h4 class="text-base font-bold text-bgray-900 dark:text-white">Project Time Progress</h4>
        </div>

        <div class="flex items-center gap-2 select-text" data-time-progress-header-badges>
            <span title="Spent time" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                Spent <span class="ml-1" data-time-progress-spent>--</span>
            </span>

            <span title="Estimated time" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                Estimate <span class="ml-1" data-time-progress-estimated>--</span>
            </span>

            @if (auth()->user()?->can('project.customer_end_date'))
                <span title="Customer estimated time" class="hidden inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50" data-time-progress-customer-badge>
                    Customer Est. <span class="ml-1" data-time-progress-customer-estimated>--</span>
                </span>
            @endif
        </div>
    </div>

    <!-- Loading State Skeleton -->
    <div data-loading-state class="space-y-4 p-5">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 rounded-[8px] border border-bgray-200 p-4 dark:border-darkblack-400">
                <div class="h-4 w-36 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                <div class="space-y-2 py-1">
                    <div class="h-2 w-full animate-pulse rounded-full bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-2 w-3/4 animate-pulse rounded-full bg-bgray-200 dark:bg-darkblack-500"></div>
                </div>
                <div class="flex gap-4">
                    <div class="h-3 w-20 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-3 w-24 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div data-empty-state class="hidden flex min-h-[160px] flex-col items-center justify-center p-6 text-center">
        <p class="text-sm text-bgray-600 dark:text-bgray-400">No time progress data available for this project.</p>
    </div>

    <!-- Task / Time Progress Content -->
    <div data-content class="hidden flex flex-col gap-4 p-5" data-time-progress-bars-container>
        <!-- Injected via AJAX -->
    </div>
</div>

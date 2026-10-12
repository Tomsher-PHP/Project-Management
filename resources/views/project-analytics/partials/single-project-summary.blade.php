<div class="space-y-4" data-single-project-summary-section data-details-url="{{ route('project.analytics.project-details') }}">
    <!-- Project Meta Card -->
    <div class="overflow-hidden rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <!-- Loading State Skeleton -->
        <div data-loading-state class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="h-6 w-48 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                <div class="flex gap-2">
                    <div class="h-6 w-16 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-6 w-16 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-2 md:grid-cols-4">
                <div class="h-4 w-28 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                <div class="h-4 w-28 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                <div class="h-4 w-28 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                <div class="h-4 w-28 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
            </div>
        </div>

        <!-- Project Meta Content -->
        <div data-content class="hidden space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-100 pb-4 dark:border-darkblack-400">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-lg font-bold text-bgray-900 dark:text-white" data-project-name>--</h2>
                        <span class="rounded bg-bgray-100 px-2 py-0.5 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300" data-project-code>--</span>
                    </div>
                    <p class="text-xs text-bgray-500 dark:text-bgray-400 mt-0.5">
                        Client: <span class="font-medium text-bgray-700 dark:text-bgray-300" data-project-customer>--</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" data-project-status-badge>
                        --
                    </span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" data-project-stage-badge>
                        --
                    </span>
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-600 dark:bg-blue-500/10 dark:text-blue-400" data-project-flow-badge>
                        --
                    </span>
                </div>
            </div>

            <!-- Service Types & Applicable Dates Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 pt-1 text-xs">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Service Types</span>
                    <div class="mt-1 flex flex-wrap gap-1.5" data-project-service-types>
                        <span class="text-bgray-400">--</span>
                    </div>
                </div>
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Start Date</span>
                    <span class="mt-1 block font-semibold text-bgray-800 dark:text-bgray-200" data-project-start-date>--</span>
                </div>
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">End / Deadline Date</span>
                    <span class="mt-1 block font-semibold text-bgray-800 dark:text-bgray-200" data-project-end-date>--</span>
                </div>
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Timeline Expiration</span>
                    <span class="mt-1 block font-semibold text-bgray-800 dark:text-bgray-200" data-project-timeline-date data-project-contract-date>--</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Summary Metrics Row -->
    <div class="custom-scroll flex items-center gap-3 overflow-x-auto py-0 dark:bg-darkblack-700">
        <!-- Total Tasks -->
        <div class="group relative flex min-w-[150px] flex-1 items-center gap-3 shrink-0 rounded-xl border border-bgray-300 bg-white p-3 transition-all duration-300 hover:border-[#d8e4f6] dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-500 dark:bg-primary-500/10">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 4.5h7M16.5 13v7" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h4 class="truncate text-[10px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">Total Tasks</h4>
                <p class="text-xl font-black leading-none text-primary-500">
                    <span data-single-task-count="total_tasks">—</span>
                </p>
            </div>
        </div>

        <!-- Active Tasks -->
        <div class="group relative flex min-w-[150px] flex-1 items-center gap-3 shrink-0 rounded-xl border border-bgray-300 bg-white p-3 transition-all duration-300 hover:border-[#d8e4f6] dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-500 dark:bg-success-500/10">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2.5-5 4 10 2.5-5H21" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h4 class="truncate text-[10px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">Active Tasks</h4>
                <p class="text-xl font-black leading-none text-success-500">
                    <span data-single-task-count="active_tasks">—</span>
                </p>
            </div>
        </div>

        <!-- Completed Tasks -->
        <div class="group relative flex min-w-[150px] flex-1 items-center gap-3 shrink-0 rounded-xl border border-bgray-300 bg-white p-3 transition-all duration-300 hover:border-[#d8e4f6] dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-500 dark:bg-purple-500/10">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12.5 10.5 15.5 16.5 8.5" />
                    <circle cx="12" cy="12" r="8" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h4 class="truncate text-[10px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">Completed Tasks</h4>
                <p class="text-xl font-black leading-none text-purple-500">
                    <span data-single-task-count="completed_tasks">—</span>
                </p>
            </div>
        </div>

        <!-- Pending Tasks -->
        <div class="group relative flex min-w-[150px] flex-1 items-center gap-3 shrink-0 rounded-xl border border-bgray-300 bg-white p-3 transition-all duration-300 hover:border-[#d8e4f6] dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-warning-50 text-warning-500 dark:bg-warning-500/10">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h4 class="truncate text-[10px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">Pending Tasks</h4>
                <p class="text-xl font-black leading-none text-warning-500">
                    <span data-single-task-count="pending_tasks">—</span>
                </p>
            </div>
        </div>

        <!-- Worked Time -->
        <div class="group relative flex min-w-[150px] flex-1 items-center gap-3 shrink-0 rounded-xl border border-bgray-300 bg-white p-3 transition-all duration-300 hover:border-[#d8e4f6] dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-500/10">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="8" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h4 class="truncate text-[10px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">Worked Time</h4>
                <p class="text-xl font-black leading-none text-blue-500">
                    <span data-single-task-count="formatted_worked_time">—</span>
                </p>
            </div>
        </div>
    </div>
</div>

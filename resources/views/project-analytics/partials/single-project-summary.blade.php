<div data-single-project-summary-host class="w-full">
    <div class="w-full h-full" data-single-project-summary-section data-details-url="{{ route('project.analytics.project-details') }}">
        <!-- Project Meta Card -->
        <div class="h-full flex flex-col justify-between overflow-hidden rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
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
                        <p class="text-xs text-bgray-700 dark:text-bgray-400 mt-0.5">
                            Customer: <span class="font-medium text-bgray-700 dark:text-bgray-300" data-project-customer>--</span>
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
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-400">Service Types</span>
                        <div class="mt-1 flex flex-wrap gap-1.5" data-project-service-types>
                            <span class="text-bgray-400">--</span>
                        </div>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-400">Start Date</span>
                        <span class="mt-1 block font-semibold text-bgray-800 dark:text-bgray-200" data-project-start-date>--</span>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-400">End / Deadline Date</span>
                        <span class="mt-1 block font-semibold text-bgray-800 dark:text-bgray-200" data-project-end-date>--</span>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-400">Timeline Expiration</span>
                        <span class="mt-1 block font-semibold text-bgray-800 dark:text-bgray-200" data-project-timeline-date data-project-contract-date>--</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

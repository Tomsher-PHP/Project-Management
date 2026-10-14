<div class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-project-analytics-chart="stage" data-url="{{ route('project.analytics.projects-by-stage') }}">
    <div class="flex items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <div>
            <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Projects by Stage</h4>
            <p class="text-xs text-bgray-700 dark:text-bgray-300">Authoritative stage workflow distribution</p>
        </div>
    </div>
    <div class="flex min-h-[360px] flex-col items-center justify-center p-5">
        <!-- Loading State Skeleton -->
        <div data-loading-state class="flex h-full w-full flex-col items-center justify-center space-y-4 py-8">
            <div class="h-36 w-36 animate-pulse rounded-full border-8 border-bgray-200 dark:border-darkblack-500"></div>
            <div class="w-full space-y-2">
                <div class="h-8 w-full animate-pulse rounded-xl bg-bgray-200 dark:bg-darkblack-500"></div>
                <div class="h-8 w-full animate-pulse rounded-xl bg-bgray-200 dark:bg-darkblack-500"></div>
            </div>
        </div>

        <!-- Empty State -->
        <div data-chart-empty-state class="hidden flex h-full w-full flex-col items-center justify-center text-center py-6">
            <div class="mb-3">
                <img src="{{ asset(config('assets.images.chart_no_data')) }}" alt="No data" class="mx-auto h-28 w-auto opacity-80">
            </div>
            <h4 class="text-sm font-semibold text-bgray-900 dark:text-white">No project stage data</h4>
            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">
                No projects found for the selected filters.
            </p>
        </div>

        <!-- Chart Content -->
        <div data-chart-content class="hidden flex w-full flex-col items-center">
            <div class="relative h-[180px] w-[180px]">
                <canvas id="projectStageChart"></canvas>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="flex h-[54px] w-[54px] flex-col items-center justify-center rounded-full bg-[#F8F8FC] text-center dark:bg-darkblack-500">
                        <span class="text-xs font-bold leading-tight text-bgray-900 dark:text-white" data-chart-total>0</span>
                        <span class="text-[9px] text-bgray-500 dark:text-bgray-300 leading-tight">Total</span>
                    </div>
                </div>
            </div>
            <div class="mt-5 w-full space-y-2 max-h-[160px] overflow-y-auto pr-1" data-chart-legend>
                <!-- Populated by JS -->
            </div>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-workspace-chart="task-priority" data-task-priority-chart-url="{{ route('analytics.chart.task-priority') }}">
    <div class="flex items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Task Priority Breakdown</h4>
    </div>
    <div class="flex min-h-[420px] flex-col items-center justify-center p-5">
        <!-- Empty State -->
        <div data-chart-empty-state class="hidden flex h-full w-full flex-col items-center justify-center text-center">
            <div class="mb-3">
                <img src="{{ asset(config('assets.images.chart_no_data')) }}" alt="No data" class="mx-auto h-32 w-auto opacity-80">
            </div>
            <h4 class="text-sm font-semibold text-bgray-900 dark:text-white">No priority data</h4>
            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-400">
                No non-completed tasks found for the selected filters.
            </p>
        </div>

        <!-- Chart Content -->
        <div data-chart-content class="flex w-full flex-col items-center">
            <div class="relative h-[180px] w-[180px]">
                <canvas id="workspaceTaskPriorityChart"></canvas>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="flex h-[54px] w-[54px] items-center justify-center rounded-full bg-[#F8F8FC] text-sm font-bold text-bgray-900 dark:bg-darkblack-500 dark:text-white" data-chart-total>
                        0
                    </div>
                </div>
            </div>
            <div class="mt-6 w-full space-y-2 max-h-[160px] overflow-y-auto pr-1" data-chart-legend>
                <!-- Legend will be populated by JS -->
            </div>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-workspace-chart="task-status" data-task-status-chart-url="{{ route('analytics.chart.task-status') }}">
    <div class="flex items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Task Status Breakdown</h4>
    </div>
    <div class="flex min-h-[320px] flex-col items-center justify-center p-5">
        <div class="relative h-[180px] w-[180px]">
            <canvas id="workspaceTaskStatusChart"></canvas>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="flex h-[54px] w-[54px] items-center justify-center rounded-full bg-[#F8F8FC] text-sm font-bold text-bgray-900 dark:bg-darkblack-500 dark:text-white" data-chart-total>
                    0
                </div>
            </div>
        </div>
        <div class="mt-6 w-full space-y-2" data-chart-legend>
            <!-- Legend will be populated by JS -->
        </div>
    </div>
</div>

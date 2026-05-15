<div class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-workspace-chart="time-comparison" data-time-comparison-chart-url="{{ route('analytics.chart.time-comparison') }}">
    <div class="flex items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Time Comparison</h4>
        <input type="date" value="{{ now()->toDateString() }}" class="h-8 rounded-lg border border-bgray-300 bg-white px-2 text-xs font-semibold text-bgray-700 outline-none focus:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-workspace-time-chart-date>
    </div>
    <div class="flex min-h-[420px] flex-col items-center justify-center p-5">
        <div class="relative h-[180px] w-[180px]">
            <canvas id="workspaceTimeComparisonChart"></canvas>
        </div>
        <div class="mt-6 w-full space-y-2 max-h-[160px] overflow-y-auto pr-1" data-chart-legend>
            <!-- Legend will be populated by JS -->
        </div>
    </div>
</div>

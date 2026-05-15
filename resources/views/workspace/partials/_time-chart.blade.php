<div class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-workspace-chart="time-comparison" data-time-comparison-chart-url="{{ route('analytics.chart.time-comparison') }}">
    <div class="flex items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Time Comparison</h4>
        <div class="flex items-center gap-2">
            <div class="flex rounded-lg border border-bgray-300 bg-white p-0.5 dark:border-darkblack-400 dark:bg-darkblack-500" data-time-filter-group>
                <style>
                    [data-time-filter].active {
                        background-color: rgb(34 197 94 / 0.1) !important;
                        color: rgb(22 163 74) !important;
                    }

                    .dark [data-time-filter].active {
                        background-color: rgb(34 197 94 / 0.2) !important;
                        color: rgb(74 222 128) !important;
                    }
                </style>
                <button type="button" class="active rounded-md px-3 py-1 text-[11px] font-bold transition-all text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white" data-time-filter="today" aria-pressed="true">Today</button>
                <button type="button" class="rounded-md px-3 py-1 text-[11px] font-bold transition-all text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white" data-time-filter="yesterday" aria-pressed="false">Yesterday</button>
                <button type="button" class="rounded-md px-3 py-1 text-[11px] font-bold transition-all text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white" data-time-filter="custom" aria-pressed="false">Custom</button>
            </div>

            <input type="text" value="{{ now()->toDateString() }}" class="h-0 w-0 border-none p-0 opacity-0" data-workspace-time-chart-date>
        </div>
    </div>

    <div class="flex min-h-[420px] flex-col items-center justify-center p-5">
        <!-- Empty State -->
        <div data-chart-empty-state class="hidden flex h-full w-full flex-col items-center justify-center text-center">
            <div class="mb-3">
                <img src="{{ asset(config('assets.images.chart_no_data')) }}" alt="No data" class="mx-auto h-32 w-auto opacity-80">
            </div>
            <h4 class="text-sm font-semibold text-bgray-900 dark:text-white">No time logged</h4>
            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                No shift, task work, or break time found for this date.
            </p>
        </div>

        <!-- Chart Content -->
        <div data-chart-content class="flex w-full flex-col items-center">
            <div class="relative h-[180px] w-[180px]">
                <canvas id="workspaceTimeComparisonChart"></canvas>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="flex h-[74px] w-[74px] flex-col items-center justify-center rounded-full bg-[#F8F8FC] text-center dark:bg-darkblack-500" data-chart-total>
                        <span class="text-[10px] font-bold uppercase text-bgray-500 dark:text-bgray-400">Worked</span>
                        <span class="text-xs font-bold text-bgray-900 dark:text-white">00h 00m</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 w-full space-y-2 max-h-[160px] overflow-y-auto pr-1" data-chart-legend>
                <!-- Legend will be populated by JS -->
            </div>
        </div>
    </div>
</div>

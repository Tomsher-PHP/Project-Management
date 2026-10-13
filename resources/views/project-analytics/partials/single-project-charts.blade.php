<div class="grid grid-cols-1 gap-4 md:grid-cols-2" data-single-project-charts-section>
    <!-- 1. Task Status Breakdown -->
    <div class="flex min-h-[380px] max-h-[420px] flex-col overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-project-chart="task-status" data-url="{{ route('project.analytics.chart.task-status') }}">
        <div class="flex flex-shrink-0 items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-500/60">
            <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Task Status Breakdown</h4>
        </div>
        <div class="flex flex-1 flex-col items-center justify-center p-5 overflow-y-auto">
            <!-- Loading State Skeleton -->
            <div data-loading-state class="flex h-full w-full flex-col items-center justify-center space-y-4 py-8">
                <div class="h-36 w-36 animate-pulse rounded-full border-8 border-bgray-200 dark:border-darkblack-500"></div>
                <div class="w-full space-y-2">
                    <div class="h-8 w-full animate-pulse rounded-xl bg-bgray-200 dark:bg-darkblack-500"></div>
                </div>
            </div>

            <!-- Empty State -->
            <div data-chart-empty-state class="hidden flex h-full w-full flex-col items-center justify-center text-center py-6">
                <div class="mb-3">
                    <img src="{{ asset(config('assets.images.chart_no_data')) }}" alt="No data" class="mx-auto h-28 w-auto opacity-80">
                </div>
                <h4 class="text-sm font-semibold text-bgray-900 dark:text-white">No task status data</h4>
                <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-400">
                    No tasks found for this project and active filters.
                </p>
            </div>

            <!-- Chart Content -->
            <div data-chart-content class="hidden flex w-full flex-col items-center">
                <div class="relative h-[180px] w-[180px]">
                    <canvas id="singleProjectTaskStatusChart"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="flex h-[54px] w-[54px] items-center justify-center rounded-full bg-[#F8F8FC] text-sm font-bold text-bgray-900 dark:bg-darkblack-500 dark:text-white" data-chart-total>
                            0
                        </div>
                    </div>
                </div>
                <div class="mt-5 w-full space-y-2 max-h-[160px] overflow-y-auto pr-1" data-chart-legend>
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- 2. User Wise Section -->
    <div class="flex min-h-[380px] max-h-[420px] flex-col overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-single-project-user-wise-section>
        <div class="flex flex-shrink-0 flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-500/60">
            <div>
                <h4 class="text-sm font-bold text-bgray-900 dark:text-white">User Wise {{ __('label.project.estimated') }} vs {{ __('label.project.spent') }}</h4>
            </div>
            <div class="flex flex-wrap items-center gap-2" data-user-wise-badges>
                <span class="inline-flex rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300" data-user-wise-spent>
                    00h : 00m {{ __('label.project.spent') }}
                </span>
                <span class="inline-flex rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300" data-user-wise-count>
                    0 users
                </span>
            </div>
        </div>

        <div class="min-h-[320px] flex-1 overflow-y-auto p-4">
            <!-- Loading State Skeleton -->
            <div data-loading-state class="space-y-3 py-2">
                <div class="h-16 w-full animate-pulse rounded-xl bg-bgray-100 dark:bg-darkblack-500"></div>
                <div class="h-16 w-full animate-pulse rounded-xl bg-bgray-100 dark:bg-darkblack-500"></div>
                <div class="h-16 w-full animate-pulse rounded-xl bg-bgray-100 dark:bg-darkblack-500"></div>
            </div>

            <!-- Content -->
            <div data-content class="hidden" data-user-wise-list-container>
                <!-- HTML from single-project-user-wise-content injected here -->
            </div>

            <!-- Empty State -->
            <div data-empty-state class="hidden flex h-[280px] items-center justify-center rounded-xl border border-dashed border-bgray-300 px-4 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                No task assignments recorded yet.
            </div>
        </div>
    </div>
</div>

<!-- 3. Time Comparison (Visible only when a specific user is selected) -->
<div class="hidden" data-single-time-comparison-wrapper>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-project-chart="time-comparison" data-url="{{ route('project.analytics.chart.time-comparison') }}">
            <div class="flex items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Time Comparison</h4>
                <div class="flex items-center gap-2">
                    <div class="flex rounded-lg border border-bgray-300 bg-white p-0.5 dark:border-darkblack-400 dark:bg-darkblack-500" data-time-filter-group>
                        <button type="button" class="active rounded-md px-2.5 py-1 text-[11px] font-bold transition-all text-bgray-600 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white" data-time-filter="today" aria-pressed="true">Today</button>
                        <button type="button" class="rounded-md px-2.5 py-1 text-[11px] font-bold transition-all text-bgray-600 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white" data-time-filter="yesterday" aria-pressed="false">Yesterday</button>
                        <button type="button" class="rounded-md px-2.5 py-1 text-[11px] font-bold transition-all text-bgray-600 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white" data-time-filter="custom" aria-pressed="false">Custom</button>
                    </div>
                    <input type="text" value="{{ now()->toDateString() }}" class="h-0 w-0 border-none p-0 opacity-0" data-project-time-chart-date>
                </div>
            </div>
            <div class="flex min-h-[380px] flex-col items-center justify-center p-5">
                <!-- Loading State Skeleton -->
                <div data-loading-state class="flex h-full w-full flex-col items-center justify-center space-y-4 py-8">
                    <div class="h-36 w-36 animate-pulse rounded-full border-8 border-bgray-200 dark:border-darkblack-500"></div>
                    <div class="w-full space-y-2">
                        <div class="h-8 w-full animate-pulse rounded-xl bg-bgray-200 dark:bg-darkblack-500"></div>
                    </div>
                </div>

                <!-- Empty State -->
                <div data-chart-empty-state class="hidden flex h-full w-full flex-col items-center justify-center text-center py-6">
                    <div class="mb-3">
                        <img src="{{ asset(config('assets.images.chart_no_data')) }}" alt="No data" class="mx-auto h-28 w-auto opacity-80">
                    </div>
                    <h4 class="text-sm font-semibold text-bgray-900 dark:text-white">No time logged</h4>
                    <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-400">
                        No time logged for this project and selected user on this date.
                    </p>
                </div>

                <!-- Chart Content -->
                <div data-chart-content class="hidden flex w-full flex-col items-center">
                    <div class="relative h-[180px] w-[180px]">
                        <canvas id="singleProjectTimeComparisonChart"></canvas>
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="flex h-[74px] w-[74px] flex-col items-center justify-center rounded-full bg-[#F8F8FC] text-center dark:bg-darkblack-500" data-chart-total>
                                <span class="text-[10px] font-bold uppercase text-bgray-700 dark:text-bgray-300">Worked</span>
                                <span class="text-xs font-bold text-bgray-900 dark:text-white" data-chart-total-val>00h 00m</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 w-full space-y-2 max-h-[160px] overflow-y-auto pr-1" data-chart-legend>
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

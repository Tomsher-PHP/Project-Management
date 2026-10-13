<div class="flex min-h-[380px] max-h-[420px] flex-col overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-single-project-assignee-chart-section>
    <div class="flex flex-shrink-0 flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <div>
            <h4 class="text-sm font-bold text-bgray-900 dark:text-white">
                User Wise
            </h4>
        </div>
        <div class="flex flex-wrap items-center gap-2" data-user-wise-chart-badges>
            <span class="inline-flex rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300" data-user-wise-chart-spent>
                00h : 00m {{ __('label.project.spent') }}
            </span>
            <span class="inline-flex rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300" data-user-wise-chart-count>
                0 users
            </span>
        </div>
    </div>

    <div class="min-h-[320px] flex-1 p-5">
        <!-- Loading State Skeleton -->
        <div data-loading-state class="flex h-[320px] w-full flex-col items-center justify-center space-y-4">
            <div class="h-60 w-full animate-pulse rounded bg-bgray-100 dark:bg-darkblack-500"></div>
        </div>

        <!-- Empty State -->
        <div data-chart-empty-state class="hidden flex h-[320px] items-center justify-center rounded-xl border border-dashed border-bgray-300 px-4 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300" data-assignee-bar-chart-empty-state>
            No task assignments recorded yet.
        </div>

        <!-- Chart Content -->
        <div data-chart-content class="hidden relative h-[320px] w-full" data-assignee-bar-chart-wrapper>
            <div class="h-full w-full" data-assignee-chart-scroll>
                <canvas id="singleProjectAssigneeBarChart" data-assignee-bar-chart aria-label="User wise {{ __('label.project.estimated') }} and {{ __('label.project.spent') }} time"></canvas>
            </div>
        </div>
    </div>
</div>

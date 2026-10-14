<div class="hidden overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-single-project-burnup-section>
    <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-darkblack-600">
        <div class="mb-4">
            <h3 class="text-base font-bold text-bgray-900 dark:text-white">
                Milestone Journey
            </h3>
            <p class="text-xs text-bgray-700 dark:text-bgray-300 mt-0.5">
                {{ __('label.project.estimated') }} vs {{ __('label.project.spent') }} cumulative hours by milestone
            </p>
        </div>

        <!-- Loading State Skeleton -->
        <div data-loading-state class="flex h-[420px] w-full flex-col items-center justify-center space-y-4">
            <div class="h-64 w-full animate-pulse rounded bg-bgray-100 dark:bg-darkblack-500"></div>
        </div>

        <!-- Empty State -->
        <div data-chart-empty-state class="hidden flex h-[420px] items-center justify-center rounded-xl border border-dashed border-bgray-300 px-6 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300" data-single-project-burnup-empty-state>
            No milestone burnup data available yet.
        </div>

        <!-- Chart Content -->
        <div data-chart-content class="hidden h-[420px]" data-single-project-burnup-chart-wrapper>
            <canvas id="singleProjectBurnupChart" data-single-project-burnup-chart aria-label="Project milestone burnup chart"></canvas>
        </div>
    </div>
</div>

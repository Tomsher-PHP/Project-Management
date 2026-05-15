<section class="space-y-4" data-workspace-charts-section>
    <div class="flex items-center gap-3">
        <span class="inline-flex h-6 w-6 items-center justify-center text-[#111653] dark:text-bgray-50">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>
        </span>
        <h3 class="text-[17px] font-extrabold tracking-normal text-bgray-800 dark:text-bgray-50">Workspace Insights</h3>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <!-- Task Status Distribution -->
        @include('workspace.partials._task-status-chart')

        <!-- Task Priority Breakdown -->
        @include('workspace.partials._task-priority-chart')

        <!-- Time Comparison -->
        @include('workspace.partials._time-chart')
    </div>
</section>

<section class="space-y-4" data-workspace-charts-section>
    <div class="grid gap-6 xl:grid-cols-3 pt-2">
        <!-- Task Status Distribution -->
        @include('analytics.partials._task-status-chart')

        <!-- Task Priority Breakdown -->
        @include('analytics.partials._task-priority-chart')

        <!-- Time Comparison -->
        @include('analytics.partials._time-chart')
    </div>
</section>

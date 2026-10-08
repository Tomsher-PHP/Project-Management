@php
    $totalTimelineWorked = $timelineProgressbars->sum(fn($b) => (int) $b->get('worked_seconds', 0));
    $totalTimelineEstimated = $timelineProgressbars->sum(fn($b) => (int) $b->get('estimated_seconds', 0));
    $totalTimelineCustomerEstimate = $timelineProgressbars->sum(fn($b) => (int) $b->get('customer_estimate_seconds', 0));
    $chartItems = $taskStatusOverview
        ->map(
            fn(array $status) => [
                'label' => $status['name'],
                'value' => $status['count'],
                'color' => $status['color'],
            ],
        )
        ->values();
    $hasChartData = $totalTaskCount > 0;
    $hasMilestoneBurnupData = !empty($milestoneBurnupChart['labels']);
    $totalWorkedSeconds = (int) $taskAssigneeOverview->sum('worked_time_seconds');
    $assigneeCount = $taskAssigneeOverview->whereNotNull('id')->count();
    $formatDuration = function (?int $seconds): string {
        $seconds = max(0, (int) $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
    };
@endphp

<div class="space-y-6" data-project-overview data-project-id="{{ $project->id }}">
    @if ($timelineProgressbars->isNotEmpty())
        <section class="overflow-hidden rounded-[10px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/70 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Project Time Progress</h4>
                </div>

                <div class="flex items-center gap-2 select-text">
                    <span title="Spent time" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                        Spent <span class="ml-1">{{ $formatDuration($totalTimelineWorked) }}</span>
                    </span>

                    <span title="Estimated time" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                        Estimate <span class="ml-1">{{ $formatDuration($totalTimelineEstimated) }}</span>
                    </span>

                    @if ($totalTimelineCustomerEstimate > 0 && auth()->user()?->can('project.customer_end_date'))
                        <span title="Customer estimated time" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                            Customer Est. <span class="ml-1">{{ $formatDuration($totalTimelineCustomerEstimate) }}</span>
                        </span>
                    @endif
                </div>
            </div>

            <!-- Task / Time Progress Content -->
            <div class="flex flex-col gap-4 p-5">
                @foreach ($timelineProgressbars as $bar)
                    @php
                        $workedSeconds = (int) $bar->get('worked_seconds', 0);
                        $estimatedSeconds = (int) $bar->get('estimated_seconds', 0);
                        $customerEstimateSeconds = (int) $bar->get('customer_estimate_seconds', 0);
                        $workedPercent = (float) $bar->get('worked_percent', 0);
                        $estimatedPercent = (float) $bar->get('estimated_percent', 0);
                        $customerEstimatePercent = (float) $bar->get('customer_estimate_percent', 0);
                        $differencePercentage = $bar->get('difference_percentage');
                        $isExceeded = (bool) $bar->get('is_exceeded', false);
                        $isActive = (bool) $bar->get('is_active', false);
                        $timelineName = $bar->get('timeline_name', 'Task Progress');
                    @endphp

                    <div class="flex flex-col gap-5 rounded-[8px] border lg:flex-row lg:items-center lg:gap-8 {{ $isActive ? 'border-success-300 bg-success-50 p-4 dark:border-darkblack-400 dark:bg-black' : 'border-bgray-200 p-4 dark:border-darkblack-400' }}">

                        <!-- Left: Task Title / Subtitle (Optional, or project name) -->
                        <div class="min-w-[140px] shrink-0">
                            <h5 class="flex items-center gap-2 text-sm font-bold text-bgray-900 dark:text-white">
                                {{ $timelineName }}
                                @if ($isActive)
                                    <span class="rounded bg-primary-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-success-400">Active</span>
                                @endif
                            </h5>
                        </div>

                        <!-- Center & Right: 3 Slim Progress Bars & Guide Lines -->
                        <div class="w-full flex-1">

                            <!-- Bars Container -->
                            <div class="relative space-y-1.5 py-1">

                                <!-- Dynamic Threshold Reference Markers (Optional vertical markers) -->
                                @if (isset($estimatedPercent) && $estimatedPercent < 100)
                                    <div class="pointer-events-none absolute top-0 bottom-0 z-10 w-px border-r border-dashed border-sky-400/70" style="left: {{ min($estimatedPercent, 100) }}%;" title="Estimated limit"></div>
                                @endif
                                @if (isset($customerEstimatePercent) && $customerEstimatePercent < 100)
                                    <div class="pointer-events-none absolute top-0 bottom-0 z-10 w-px bg-purple-400/80" style="left: {{ min($customerEstimatePercent, 100) }}%;" title="Customer limit"></div>
                                @endif

                                <!-- Bar 1: Spent Hours -->
                                <div class="relative h-2 w-full overflow-hidden rounded-full bg-bgray-100 dark:bg-darkblack-500">
                                    @php
                                        // Spent Color Logic based on limits
                                        $spentGradient = 'bg-emerald-500';
                                        if (isset($customerEstimateSeconds) && $customerEstimateSeconds > 0 && $workedSeconds > $customerEstimateSeconds) {
                                            $spentGradient = 'bg-gradient-to-r from-red-500 via-rose-500 to-amber-500';
                                        } elseif ($workedSeconds > $estimatedSeconds) {
                                            $spentGradient = 'bg-gradient-to-r from-amber-500 to-orange-500';
                                        }
                                    @endphp
                                    <div class="h-full rounded-full transition-all duration-500 {{ $spentGradient }}" style="width: {{ min($workedPercent, 100) }}%;"></div>
                                </div>

                                <!-- Bar 2: Internal Estimated Hours -->
                                <div class="relative h-2 w-full overflow-hidden rounded-full bg-bgray-100 dark:bg-darkblack-500">
                                    <div class="h-full rounded-full bg-sky-500 transition-all duration-500" style="width: {{ min($estimatedPercent, 100) }}%;"></div>
                                </div>

                                <!-- Bar 3: Customer Estimated Hours -->
                                @if ($customerEstimateSeconds > 0 && auth()->user()?->can('project.customer_end_date'))
                                    <div class="relative h-2 w-full overflow-hidden rounded-full bg-bgray-100 dark:bg-darkblack-500">
                                        <div class="h-full rounded-full bg-purple-500 transition-all duration-500" style="width: {{ min($customerEstimatePercent, 100) }}%;"></div>
                                    </div>
                                @endif
                            </div>

                            <!-- Bottom Metadata Legend with Dots -->
                            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-bgray-600 dark:text-darkblack-200">
                                <!-- Spent Legend -->
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full {{ $workedSeconds > $estimatedSeconds ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                                    <span class="text-bgray-700 dark:text-bgray-300">{{ __('label.project.spent') }}:</span>
                                    <span class="font-semibold text-bgray-900 dark:text-white">{{ $formatDuration($workedSeconds) }}</span>
                                </div>

                                <!-- Estimated Legend -->
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                                    <span class="text-bgray-700 dark:text-bgray-300">{{ __('label.project.estimated') }}:</span>
                                    <span class="font-semibold text-bgray-900 dark:text-white">{{ $formatDuration($estimatedSeconds) }}</span>
                                </div>

                                <!-- Customer Estimated Legend -->
                                @if ($customerEstimateSeconds > 0 && auth()->user()?->can('project.customer_end_date'))
                                    <div class="flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                                        <span class="text-bgray-700 dark:text-bgray-300">{{ __('label.project.customer_estimated') }}:</span>
                                        <span class="font-semibold text-bgray-900 dark:text-white">{{ $formatDuration($customerEstimateSeconds) }}</span>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($project->isAgile)
        <!-- Milestone burn up chart -->
        <div class="grid gap-6 xl:grid-cols-1">
            <section class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-darkblack-600">
                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">
                            Milestone Journey
                        </h3>
                        <p class="text-sm text-bgray-700 dark:text-bgray-300">
                            {{ __('label.project.estimated') }} vs {{ __('label.project.spent') }} cumulative hours by milestone
                        </p>
                    </div>

                    <script type="application/json" data-project-overview-burnup-data>@json($milestoneBurnupChart)</script>

                    <div class="{{ $hasMilestoneBurnupData ? '' : 'hidden' }} h-[420px]" data-project-overview-burnup-chart-wrapper>
                        <canvas data-project-overview-burnup-chart aria-label="Project milestone burnup chart"></canvas>
                    </div>

                    <div class="{{ $hasMilestoneBurnupData ? 'hidden' : '' }} flex h-[420px] items-center justify-center rounded-xl border border-dashed border-bgray-300 px-6 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300" data-project-overview-burnup-empty-state>
                        No milestone burnup data available yet.
                    </div>
                </div>
            </section>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-2 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Task Status Breakdown</h4>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">
                        {{ $totalTaskCount }} {{ \Illuminate\Support\Str::plural('task', $totalTaskCount) }}
                    </span>
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">
                        {{ $taskStatusOverview->count() }} {{ \Illuminate\Support\Str::plural('status', $taskStatusOverview->count()) }}
                    </span>
                </div>
            </div>

            <div class="min-h-[320px] p-5">
                <script type="application/json" data-project-overview-chart-data>@json($chartItems)</script>

                <div class="{{ $hasChartData ? '' : 'hidden' }} flex h-full flex-col gap-8 lg:flex-row lg:items-center lg:justify-between" data-project-overview-chart-wrapper>
                    <div class="flex items-center justify-center lg:flex-[1.15]">
                        <div class="relative w-[220px] md:w-[240px]">
                            <canvas data-project-overview-chart height="220" aria-label="Project task status chart"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="flex h-[64px] w-[64px] items-center justify-center rounded-full bg-[#F8F8FC] text-base font-bold text-bgray-900 dark:bg-darkblack-500 dark:text-white" data-project-overview-chart-total>
                                    {{ $totalTaskCount }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 lg:w-full lg:max-w-[320px] lg:flex-[0.85]">
                        @forelse ($taskStatusOverview as $status)
                            @php
                                $percentage = $totalTaskCount > 0 ? round(($status['count'] / $totalTaskCount) * 100) : 0;
                            @endphp

                            <div class="flex items-center justify-between gap-4 rounded-xl border border-bgray-200 px-4 py-3 dark:border-darkblack-400">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background-color: {{ $status['color'] }};"></span>
                                    <span class="truncate text-sm font-semibold text-bgray-700 dark:text-bgray-300">{{ $status['name'] }}</span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold text-bgray-900 dark:text-white">{{ $status['count'] }}</span>
                                    <span class="rounded-full bg-bgray-50 px-2.5 py-1 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                                        {{ $percentage }}%
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                                No task statuses available for this project flow.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="{{ $hasChartData ? 'hidden' : '' }} flex h-full min-h-[280px] items-center justify-center rounded-xl border border-dashed border-bgray-300 px-6 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300" data-project-overview-empty-state>
                    No tasks found for this project yet.
                </div>
            </div>
        </section>

        <section class="flex min-h-[380px] max-h-[400px] flex-col overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-shrink-0 flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-2 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">User Wise</h4>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">
                        {{ $formatDuration($totalWorkedSeconds) }} {{ __('label.project.spent') }}
                    </span>
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">
                        {{ $assigneeCount }} {{ \Illuminate\Support\Str::plural('user', $assigneeCount) }}
                    </span>
                </div>
            </div>

            <div class="min-h-[320px] flex-1 overflow-y-auto p-5">
                @if ($taskAssigneeOverview->isEmpty())
                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                        No task assignments recorded yet.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($taskAssigneeOverview as $assignee)
                            @php
                                $workedSeconds = (int) ($assignee['worked_time_seconds'] ?? 0);
                                $estimatedSeconds = (int) ($assignee['estimated_time_seconds'] ?? 0);
                                $hasEstimatedTime = $estimatedSeconds > 0;
                                $isWithinEstimate = $hasEstimatedTime && $workedSeconds <= $estimatedSeconds;
                                $comparisonPercentage = $hasEstimatedTime ? (int) round((abs($estimatedSeconds - $workedSeconds) / $estimatedSeconds) * 100) : 0;

                                $comparisonClasses = !$hasEstimatedTime ? 'text-bgray-900 dark:text-white' : ($isWithinEstimate ? 'text-success-400 dark:text-success-300' : 'text-red-500 dark:text-red-400');
                            @endphp
                            <div class="flex items-center justify-between gap-4 rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                                <a href="{{ route('reports.time_tracking', ['project_id' => [$project->id], 'user_id' => [$assignee['id']], 'request_status' => 'approved']) }}" target="_blank" class="flex min-w-0 items-center gap-3 group">
                                    <x-user-avatar :name="$assignee['name']" :image="$assignee['profile_image_url']" class="h-10 w-10 flex-shrink-0" />

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-bgray-900 dark:text-white group-hover:text-success-300 transition">{{ $assignee['name'] }}</p>
                                        <p class="text-xs text-bgray-700 dark:text-bgray-300 group-hover:text-success-300/80 transition">{{ $assignee['count'] }} {{ \Illuminate\Support\Str::plural('task', $assignee['count']) }} involved</p>
                                    </div>
                                </a>

                                <div class="flex items-center justify-end gap-4 text-right">
                                    <div>
                                        <p class="text-sm font-bold text-bgray-900 dark:text-white">
                                            {{ $formatDuration($estimatedSeconds) }}
                                        </p>
                                        <p class="text-xs text-bgray-700 dark:text-bgray-300">{{ __('label.project.estimated') }}</p>
                                    </div>

                                    <div>
                                        <p class="text-sm font-bold text-bgray-900 dark:text-white">
                                            {{ $formatDuration($workedSeconds) }}
                                        </p>
                                        <p class="text-xs text-bgray-700 dark:text-bgray-300">{{ __('label.project.spent') }}</p>
                                    </div>


                                    <div>
                                        <p class="inline-flex items-center justify-end gap-1 text-xs font-semibold {{ $comparisonClasses }}">

                                            @if ($hasEstimatedTime)
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $isWithinEstimate ? 'comparison-arrow-up' : '' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.06 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                                                </svg>
                                            @endif

                                            {{ $comparisonPercentage }}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-1">
        <section class="flex min-h-[380px] max-h-[400px] flex-col overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-shrink-0 flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-2 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                        User Wise
                    </h4>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">
                        {{ $formatDuration($totalWorkedSeconds) }} {{ __('label.project.spent') }}
                    </span>

                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">
                        {{ $assigneeCount }}
                        {{ \Illuminate\Support\Str::plural('user', $assigneeCount) }}
                    </span>
                </div>
            </div>

            <div class="min-h-[320px] flex-1 p-5">
                <script type="application/json" data-assignee-bar-chart-data>
                    @json($taskAssigneeOverview)
                </script>

                <div class="min-h-[320px] flex-1 p-5">
                    @if ($taskAssigneeOverview->isEmpty())
                        <div class="flex h-[320px] items-center justify-center rounded-xl border border-dashed border-bgray-300 px-4 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                            No task assignments recorded yet.
                        </div>
                    @else
                        <div class="relative h-[320px] w-full" data-assignee-bar-chart-wrapper>
                            <div class="h-full w-full" data-assignee-chart-scroll>
                                <canvas data-assignee-bar-chart aria-label="User wise {{ __('label.project.estimated') }} and {{ __('label.project.spent') }} time"></canvas>
                            </div>
                        </div>
                    @endif

                    <script type="application/json" data-assignee-bar-chart-data>
                        @json($taskAssigneeOverview)
                    </script>
                </div>
            </div>
        </section>
    </div>
</div>

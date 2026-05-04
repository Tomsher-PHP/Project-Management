@php
    $workedSeconds = (int) $progressbar->get('worked_seconds', 0);
    $estimatedSeconds = (int) $progressbar->get('estimated_seconds', 0);
    $workedPercent = (float) $progressbar->get('worked_percent', 0);
    $estimatedPercent = (float) $progressbar->get('estimated_percent', 0);
    $differencePercentage = $progressbar->get('difference_percentage');
    $hasEstimate = (bool) $progressbar->get('has_estimate', false);
    $isExceeded = (bool) $progressbar->get('is_exceeded', false);
    $isWithinEstimate = $hasEstimate && ! $isExceeded;
    $statusLabel = (string) $progressbar->get('status_label', 'No estimate added');
    $statusTextColor = (string) $progressbar->get('status_text_color', 'text-bgray-500 dark:text-bgray-300');
    $workedBarColor = (string) $progressbar->get('worked_bar_color', 'bg-green-500');
    $estimatedBarColor = (string) $progressbar->get('estimated_bar_color', 'bg-bgray-400 dark:bg-bgray-300');
    $comparisonClasses = $isWithinEstimate ? 'text-success-400 dark:text-success-300' : 'text-red-500 dark:text-red-400';
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
    @if ($hasEstimate)
        <section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Project Time Progress</h4>
                </div>

                <div class="text-right">
                    <p class="text-sm font-bold {{ $statusTextColor }}">{{ $statusLabel }}</p>
                    <p class="inline-flex items-center justify-end gap-1 text-xs font-semibold {{ $comparisonClasses }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $isWithinEstimate ? 'comparison-arrow-up' : '' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.06 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                        </svg>
                        {{ $differencePercentage }}%
                    </p>
                </div>
            </div>

            <div class="space-y-5 p-5">
                <div class="rounded-xl">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">Worked</p>
                        </div>
                        <p class="text-sm font-bold text-bgray-900 dark:text-white">{{ $formatDuration($workedSeconds) }}</p>
                    </div>

                    <div class="h-4 w-full overflow-hidden rounded-full bg-bgray-100 dark:bg-darkblack-500">
                        <div class="h-full rounded-full transition-all duration-500 {{ $workedBarColor }}" style="width: {{ $workedPercent }}%;"></div>
                    </div>
                </div>

                <div class="rounded-xl">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">Estimated</p>
                        </div>
                        <p class="text-sm font-bold text-bgray-900 dark:text-white">{{ $formatDuration($estimatedSeconds) }}</p>
                    </div>

                    <div class="h-4 w-full overflow-hidden rounded-full bg-bgray-100 dark:bg-darkblack-500">
                        <div class="h-full rounded-full transition-all duration-500 {{ $estimatedBarColor }}" style="width: {{ $estimatedPercent }}%;"></div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Task Status Breakdown</h4>
                    <p class="text-sm text-bgray-500 dark:text-bgray-300">See how project tasks are distributed across each workflow status.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                        {{ $totalTaskCount }} {{ \Illuminate\Support\Str::plural('task', $totalTaskCount) }}
                    </span>
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
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
                                    <span class="truncate text-sm font-semibold text-bgray-700 dark:text-bgray-100">{{ $status['name'] }}</span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold text-bgray-900 dark:text-white">{{ $status['count'] }}</span>
                                    <span class="rounded-full bg-bgray-50 px-2.5 py-1 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                                        {{ $percentage }}%
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                                No task statuses available for this project flow.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="{{ $hasChartData ? 'hidden' : '' }} flex h-full min-h-[280px] items-center justify-center rounded-xl border border-dashed border-bgray-300 px-6 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300" data-project-overview-empty-state>
                    No tasks found for this project yet.
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">User Wise</h4>
                    <p class="text-sm text-bgray-500 dark:text-bgray-300">Worked duration and involved tasks by user.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                        {{ $formatDuration($totalWorkedSeconds) }} worked
                    </span>
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                        {{ $assigneeCount }} {{ \Illuminate\Support\Str::plural('user', $assigneeCount) }}
                    </span>
                </div>
            </div>

            <div class="max-h-[560px] min-h-[320px] overflow-y-auto p-5">
                @if ($taskAssigneeOverview->isEmpty())
                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
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
                                $comparisonPercentage = $hasEstimatedTime ? (int) round((abs($estimatedSeconds - $workedSeconds) / $estimatedSeconds) * 100) : null;
                                $comparisonClasses = $isWithinEstimate ? 'text-success-400 dark:text-success-300' : 'text-red-500 dark:text-red-400';
                            @endphp
                            <div class="flex items-center justify-between gap-4 rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                                <div class="flex min-w-0 items-center gap-3">
                                    @if ($assignee['profile_image_url'])
                                        <img src="{{ $assignee['profile_image_url'] }}" alt="{{ $assignee['name'] }}" class="h-10 w-10 flex-shrink-0 rounded-full object-cover">
                                    @else
                                        <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-bgray-100 text-sm font-bold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($assignee['name'], 0, 1)) }}
                                        </span>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-bgray-900 dark:text-white">{{ $assignee['name'] }}</p>
                                        <p class="text-xs text-bgray-500 dark:text-bgray-300">{{ $assignee['count'] }} {{ \Illuminate\Support\Str::plural('task', $assignee['count']) }} involved</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-4 text-right">
                                    <div>
                                        <p class="text-sm font-bold text-bgray-900 dark:text-white">
                                            {{ $formatDuration($estimatedSeconds) }}
                                        </p>
                                        <p class="text-xs text-bgray-500 dark:text-bgray-300">Estimated</p>
                                    </div>

                                    <div>
                                        <p class="text-sm font-bold text-bgray-900 dark:text-white">
                                            {{ $formatDuration($workedSeconds) }}
                                        </p>
                                        <p class="text-xs text-bgray-500 dark:text-bgray-300">Worked</p>
                                    </div>


                                    <div>
                                        <p class="inline-flex items-center justify-end gap-1 text-xs font-semibold {{ $comparisonClasses }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $isWithinEstimate ? 'comparison-arrow-up' : '' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.06 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                                            </svg>
                                            {{ $comparisonPercentage ?? 0 }}%
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

    @if ($project->isAgile)
        <!-- Milestone burn up chart -->
        <div class="grid gap-6 xl:grid-cols-1">
            <section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-darkblack-600">
                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">
                            Milestone Journey
                        </h3>
                        <p class="text-sm text-bgray-500 dark:text-bgray-300">
                            Estimated vs actual cumulative hours by milestone
                        </p>
                    </div>

                    <script type="application/json" data-project-overview-burnup-data>@json($milestoneBurnupChart)</script>

                    <div class="{{ $hasMilestoneBurnupData ? '' : 'hidden' }} h-[420px]" data-project-overview-burnup-chart-wrapper>
                        <canvas data-project-overview-burnup-chart aria-label="Project milestone burnup chart"></canvas>
                    </div>

                    <div class="{{ $hasMilestoneBurnupData ? 'hidden' : '' }} flex h-[420px] items-center justify-center rounded-xl border border-dashed border-bgray-300 px-6 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300" data-project-overview-burnup-empty-state>
                        No milestone burnup data available yet.
                    </div>
                </div>
            </section>
        </div>
    @endif

</div>

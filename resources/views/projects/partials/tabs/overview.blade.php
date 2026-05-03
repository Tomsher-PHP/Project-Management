@php
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
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Tasks</h4>
                    <p class="text-sm text-bgray-500 dark:text-bgray-300">Status distribution for this project.</p>
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
                    <div class="flex items-center justify-center">
                        <div class="relative w-[180px]">
                            <canvas data-project-overview-chart height="168" aria-label="Project task status chart"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="flex h-[52px] w-[52px] items-center justify-center rounded-full bg-[#F8F8FC] text-sm font-bold text-bgray-900 dark:bg-darkblack-500 dark:text-white" data-project-overview-chart-total>
                                    {{ $totalTaskCount }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 space-y-3">
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

                                <div class="text-right">
                                    <p class="text-sm font-bold text-bgray-900 dark:text-white">{{ $formatDuration($assignee['worked_time_seconds']) }}</p>
                                    <p class="text-xs text-bgray-500 dark:text-bgray-300">worked</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

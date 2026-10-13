@php
    $formatDuration = $formatDuration ?? function (?int $seconds): string {
        $seconds = max(0, (int) $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
    };
@endphp

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

                <div class="flex items-center justify-end gap-2.5 sm:gap-4 text-right flex-shrink-0">
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

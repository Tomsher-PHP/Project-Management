@php
    $totalActivities = $activities->count();
    $moduleLabel = 'Task';
    $subjectLabel = $task->name;
    $ignoredFields = ['created_at', 'updated_at', 'deleted_at', 'added_by', 'updated_by'];
@endphp

<div class="flex min-h-0 flex-1 flex-col overflow-hidden">
    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-success-400">{{ $moduleLabel }} Activity</p>
            <h3 class="mt-2 text-2xl font-bold text-bgray-900 dark:text-white">{{ $subjectLabel }}</h3>
            <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                {{ $totalActivities }} recent {{ \Illuminate\Support\Str::plural('update', $totalActivities) }} related to this {{ \Illuminate\Support\Str::lower($moduleLabel) }}.
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            @if (!empty($viewAllUrl))
                <a href="{{ $viewAllUrl }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-success-200 bg-success-50 px-4 text-sm font-semibold text-success-500 transition hover:border-success-300 hover:bg-success-300 hover:text-white dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300 dark:hover:border-success-300 dark:hover:bg-success-300 dark:hover:text-white">
                    View All
                </a>
            @endif

            <button type="button" data-task-insights-close class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-bgray-100 text-bgray-600 transition hover:bg-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.5 4.5L4.5 13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    <path d="M4.5 4.5L13.5 13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
            </button>
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-6 py-6">
        <div class="space-y-4">
            @forelse ($activities as $activity)
                @php
                    $event = $activity->event ?? 'updated';
                    $actorName = $activity->causer?->name ?? 'System';
                    $actorImageUrl = $activity->causer?->profile_image_url ?? asset(config('assets.images.default_avatar'));
                    $subject = $activity->subject;
                    $subjectType = $activity->subject_type ? \Illuminate\Support\Str::headline(class_basename($activity->subject_type)) : \Illuminate\Support\Str::headline($activity->log_name ?? 'Activity');
                    $activitySubjectLabel = $subject?->name ?? ($subject?->title ?? ($subject?->original_name ?? ($subject?->file_name ?? ($subject?->project_code ?? ($subject?->customer_code ?? ($subject?->employee_id ?? ($activity->subject_id ? '#' . $activity->subject_id : '--')))))));
                    $labels = collect($activity->getExtraProperty('labels', []));
                    $visibleChanges = collect($activity->changes->get('attributes', []))
                        ->keys()
                        ->merge(collect($activity->changes->get('old', []))->keys())
                        ->unique()
                        ->reject(fn($field) => in_array($field, $ignoredFields, true))
                        ->map(fn($field) => $labels->get($field, (string) \Illuminate\Support\Str::of($field)->replace('_id', '')->replace('_', ' ')->title()))
                        ->values();
                    $eventClasses = match ($event) {
                        'created' => 'bg-success-50 text-success-400 dark:bg-success-900/20 dark:text-success-300',
                        'deleted' => 'bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-300',
                        'restored' => 'bg-warning-50 text-warning-500 dark:bg-warning-900/20 dark:text-warning-300',
                        default => 'bg-blue-50 text-blue-500 dark:bg-blue-900/20 dark:text-blue-300',
                    };
                    $summary = match ($event) {
                        'created' => $visibleChanges->isNotEmpty() ? 'Created ' . $visibleChanges->count() . ' ' . \Illuminate\Support\Str::plural('field', $visibleChanges->count()) . '.' : 'Created a new record.',
                        'deleted' => $visibleChanges->isNotEmpty() ? 'Removed a record with ' . $visibleChanges->count() . ' tracked ' . \Illuminate\Support\Str::plural('field', $visibleChanges->count()) . '.' : 'Removed a record.',
                        'restored' => $visibleChanges->isNotEmpty() ? 'Restored ' . $visibleChanges->count() . ' ' . \Illuminate\Support\Str::plural('field', $visibleChanges->count()) . '.' : 'Restored a record.',
                        default => $visibleChanges->isNotEmpty() ? 'Updated ' . $visibleChanges->count() . ' ' . \Illuminate\Support\Str::plural('field', $visibleChanges->count()) . '.' : 'Updated a record.',
                    };
                @endphp

                <article class="rounded-2xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                    <div class="flex items-start gap-3">
                        <img src="{{ $actorImageUrl }}" alt="{{ $actorName }}" class="h-10 w-10 shrink-0 rounded-xl border border-bgray-200 object-cover shadow-sm dark:border-darkblack-400" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0 flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                                        {{ $actorName }}
                                    </p>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $eventClasses }}">
                                        {{ \Illuminate\Support\Str::headline($event) }}
                                    </span>
                                    <x-activity-log.view-button :activity="$activity" label="View Details" class="h-7 rounded-full px-3" />
                                </div>

                                <p class="shrink-0 pt-0.5 text-xs font-medium text-bgray-700 dark:text-bgray-300">
                                    {{ $activity->created_at?->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) }}
                                </p>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <p class="text-sm text-bgray-600 dark:text-bgray-300">
                                    {{ $summary }}
                                </p>
                                <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-bgray-700 dark:text-bgray-300">
                                    {{ $subjectType }}: {{ $activitySubjectLabel }}
                                </p>
                            </div>

                            @if ($visibleChanges->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($visibleChanges->take(4) as $label)
                                        <span class="rounded-full bg-bgray-100 px-2.5 py-0.5 text-[11px] font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                            {{ $label }}
                                        </span>
                                    @endforeach

                                    @if ($visibleChanges->count() > 4)
                                        <span class="rounded-full bg-bgray-100 px-2.5 py-0.5 text-[11px] font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                            +{{ $visibleChanges->count() - 4 }} more
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-bgray-300 px-6 py-12 text-center text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                    No activity logged for this {{ \Illuminate\Support\Str::lower($moduleLabel) }} or its related records yet.
                </div>
            @endforelse
        </div>
    </div>
</div>

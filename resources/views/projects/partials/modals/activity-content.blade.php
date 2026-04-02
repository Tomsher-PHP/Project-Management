@php
    $totalActivities = $activities->count();
@endphp

<div class="flex h-full flex-col">
    <div class="flex items-start justify-between gap-4 border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-success-400">Project Activity</p>
            <h3 class="mt-2 text-2xl font-bold text-bgray-900 dark:text-white">{{ $project->name }}</h3>
            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                {{ $totalActivities }} recent {{ \Illuminate\Support\Str::plural('update', $totalActivities) }} from this project.
            </p>
        </div>

        <button type="button" data-project-insights-close class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-bgray-100 text-bgray-600 transition hover:bg-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.5 4.5L4.5 13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                <path d="M4.5 4.5L13.5 13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-6 py-6">
        <div class="space-y-4">
            @forelse ($activities as $activity)
                @php
                    $event = $activity->event ?? 'updated';
                    $visibleChanges = collect($activity->changes['attributes'] ?? [])
                        ->except(['created_at', 'updated_at', 'deleted_at', 'added_by', 'updated_by']);
                    $eventClasses = match ($event) {
                        'created' => 'bg-success-50 text-success-400 dark:bg-success-900/20 dark:text-success-300',
                        'deleted' => 'bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-300',
                        'restored' => 'bg-warning-50 text-warning-500 dark:bg-warning-900/20 dark:text-warning-300',
                        default => 'bg-blue-50 text-blue-500 dark:bg-blue-900/20 dark:text-blue-300',
                    };
                    $summary = match ($event) {
                        'created' => 'Created a new project-related record.',
                        'deleted' => 'Removed a project-related record.',
                        'restored' => 'Restored a previously removed record.',
                        default => $visibleChanges->isNotEmpty()
                            ? 'Updated ' . $visibleChanges->count() . ' ' . \Illuminate\Support\Str::plural('field', $visibleChanges->count()) . '.'
                            : 'Updated a project-related record.',
                    };
                @endphp

                <article class="rounded-2xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-success-50 text-sm font-bold text-success-400 dark:bg-success-900/20 dark:text-success-300">
                                {{ \Illuminate\Support\Str::of($activity->causer?->name ?? 'System')->trim()->substr(0, 2)->upper() }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                                        {{ $activity->causer?->name ?? 'System' }}
                                    </p>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $eventClasses }}">
                                        {{ \Illuminate\Support\Str::headline($event) }}
                                    </span>
                                </div>

                                <p class="mt-2 text-sm leading-6 text-bgray-600 dark:text-bgray-300">
                                    {{ $summary }}
                                </p>

                                @if ($visibleChanges->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($visibleChanges->keys()->take(4) as $field)
                                            <span class="rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200">
                                                {{ \Illuminate\Support\Str::headline($field) }}
                                            </span>
                                        @endforeach

                                        @if ($visibleChanges->count() > 4)
                                            <span class="rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200">
                                                +{{ $visibleChanges->count() - 4 }} more
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <p class="shrink-0 text-xs font-medium text-bgray-500 dark:text-bgray-300">
                            {{ $activity->created_at?->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) }}
                        </p>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-bgray-300 px-6 py-12 text-center text-sm font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                    No activity logged for this project yet.
                </div>
            @endforelse
        </div>
    </div>
</div>

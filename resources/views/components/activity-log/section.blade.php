@props([
    'title' => 'Activity Log',
    'activities' => collect(),
    'emptyMessage' => 'No activity logged yet.',
    'viewAllUrl' => null,
])

<div class="flex w-full flex-col rounded-lg bg-white dark:border dark:border-darkblack-400 dark:bg-darkblack-600">
    <div class="flex items-center justify-between gap-3 border-b border-bgray-300 px-[26px] py-6 dark:border-darkblack-400">
        <h1 class="text-2xl font-semibold text-bgray-900 dark:text-white">
            {{ $title }}
        </h1>

        @if ($viewAllUrl)
            <a href="{{ $viewAllUrl }}" class="inline-flex items-center justify-center rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm font-medium text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-300 hover:text-white dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300 dark:hover:border-success-300 dark:hover:bg-success-300 dark:hover:text-white">
                View All
            </a>
        @endif
    </div>

    <div class="w-full px-5 py-6 lg:px-[35px] lg:py-[30px]">
        <div class="flex flex-col space-y-4">
            @forelse ($activities as $activity)
                @php
                    $event = $activity->event ?? 'updated';
                    $changedValues = collect($activity->changes->get('attributes', []))
                        ->except(['added_by', 'updated_by']);
                    $changedAttributes = $changedValues->keys()->take(3);
                    $remainingChanges = max($changedValues->count() - $changedAttributes->count(), 0);
                    $eventClasses = match ($event) {
                        'created' => 'bg-success-50 text-success-400',
                        'deleted' => 'bg-red-50 text-red-500',
                        'restored' => 'bg-warning-50 text-warning-500',
                        default => 'bg-blue-50 text-blue-500',
                    };
                @endphp

                <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                                {{ $activity->causer?->name ?? 'System' }}
                            </p>
                            <p class="text-xs text-bgray-500 dark:text-bgray-300">
                                {{ $activity->created_at?->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) }}
                            </p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $eventClasses }}">
                            {{ \Illuminate\Support\Str::headline($event) }}
                        </span>
                    </div>

                    @if ($changedAttributes->isNotEmpty())
                        <p class="text-sm text-bgray-700 dark:text-bgray-200">
                            Changed:
                            {{ $changedAttributes->map(fn ($attribute) => \Illuminate\Support\Str::headline($attribute))->implode(', ') }}
                            @if ($remainingChanges > 0)
                                +{{ $remainingChanges }} more
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-bgray-700 dark:text-bgray-200">
                            {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $activity->description)) }}
                        </p>
                    @endif

                    <x-activity-log.view-button
                        :activity="$activity"
                        label="View Details"
                        class="mt-4 w-full justify-center"
                    />
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-bgray-300 px-4 py-6 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                    {{ $emptyMessage }}
                </div>
            @endforelse
        </div>
    </div>
</div>

<x-activity-log.details-modal />

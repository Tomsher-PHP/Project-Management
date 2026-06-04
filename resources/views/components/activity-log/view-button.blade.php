@props([
    'activity',
    'label' => 'View',
])

@php
    $ignoredFields = ['created_at', 'updated_at', 'deleted_at', 'added_by', 'updated_by'];
    $event = $activity->event ?? 'updated';
    $changeFields = collect($activity->changes->get('attributes', []))
        ->keys()
        ->merge(collect($activity->changes->get('old', []))->keys())
        ->unique()
        ->reject(fn($field) => in_array($field, $ignoredFields, true));
    $canViewDetails = in_array($event, ['created', 'updated', 'deleted', 'restored'], true) && $changeFields->isNotEmpty();
@endphp

@if ($canViewDetails)
    <button
        type="button"
        data-activity-log-view
        data-activity-log-url="{{ route('activity.log.details', $activity) }}"
        {{ $attributes->merge([
            'class' => 'inline-flex h-8 items-center justify-center rounded-lg border bg-success-50 px-2.5 text-xs font-semibold text-success-400 shadow-sm transition duration-200 hover:bg-success-300 hover:text-white focus:outline-none dark:border-success-300 dark:bg-darkblack-500 dark:text-success-300',
        ]) }}
    >
        <span class="text-current transition-colors duration-200">
            {{ $label }}
        </span>
    </button>
@endif

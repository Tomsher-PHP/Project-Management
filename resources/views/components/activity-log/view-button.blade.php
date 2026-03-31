@props([
    'activity',
    'label' => 'View',
])

@php
    $ignoredFields = ['created_at', 'updated_at', 'deleted_at', 'added_by', 'updated_by'];
    $event = $activity->event ?? 'updated';
    $changeAttributes = collect($activity->changes->get('attributes', []))->except($ignoredFields);
    $canViewDetails = in_array($event, ['created', 'updated'], true) && $changeAttributes->isNotEmpty();
@endphp

@if ($canViewDetails)
    <button
        type="button"
        data-activity-log-view
        data-activity-log-url="{{ route('activity.log.details', $activity) }}"
        {{ $attributes->merge([
            'class' => 'inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-600 transition duration-200 hover:border-blue-500 hover:bg-blue-500 hover:text-white dark:border-blue-900/40 dark:bg-darkblack-500 dark:text-blue-400 dark:hover:border-blue-500 dark:hover:bg-blue-500 dark:hover:text-white',
        ]) }}
    >
        {{ $label }}
    </button>
@endif

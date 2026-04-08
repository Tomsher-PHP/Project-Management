@props([
    'title' => 'Project Flow',
])

@php
    $flowOptions = collect(config('project_constants.project_flows', []))
        ->only(['agile', 'linear'])
        ->filter();
@endphp

<div {{ $attributes->merge([
    'class' => 'inline-flex flex-wrap items-center gap-2 rounded-xl border border-bgray-200 bg-white px-3 py-2 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600',
]) }}>
    <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-bgray-500 dark:text-bgray-300">
        {{ $title }}
    </span>

    @foreach ($flowOptions as $flowKey => $flowLabel)
        <span class="inline-flex items-center gap-2 rounded-full bg-bgray-50 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-100">
            <x-project-flow-icon :flow="$flowKey" size="sm" />
            <span>{{ $flowLabel }}</span>
        </span>
    @endforeach
</div>

@props(['name', 'requestType' => null, 'requestStatus' => null, 'limit' => null, 'limitEnd' => '...', 'truncate' => true, 'display' => 'inline-flex', 'textClass' => 'text-gray-900', 'nameClass' => '', 'showPriorityDot' => false, 'showPriorityIndicator' => false, 'priorityIndicator' => 'dot', 'priorityClass' => 'bg-primary'])

@php
    $isSelf = $requestType === 'self';

    $statusTextClass = '';
    $icon = null;
    $tooltip = '';

    if ($isSelf) {
        if ($requestStatus === 'pending') {
            $statusTextClass = 'text-warning-300';
            $tooltip = 'Pending approval : '.$name;
            $icon = 'hourglass';
        } elseif ($requestStatus === 'rejected') {
            $statusTextClass = 'text-error-300 font-medium';
            $tooltip = 'Rejected : '.$name;
            $icon = 'lock';
        }
    }

    $displayName = filled($limit) ? \Illuminate\Support\Str::limit($name ?? '', (int) $limit, $limitEnd) : $name ?? '';

    $resolvedTitle = $tooltip ?: $name ?? '';
    $containerClasses = trim($display . ' min-w-0 items-center gap-1 ' . $textClass . ' ' . $statusTextClass);
    $resolvedNameClass = trim(($truncate ? 'truncate' : '') . ' min-w-0 ' . $nameClass);
@endphp

<span {{ $attributes->merge([
    'class' => $containerClasses,
]) }} @if ($resolvedTitle) title="{{ $resolvedTitle }}" @endif>
    @php
        $shouldShowPriorityIndicator = $showPriorityIndicator || $showPriorityDot;
    @endphp

    @if ($shouldShowPriorityIndicator)
        @if ($priorityIndicator === 'line')
            <span class="mr-2 h-6 w-1 flex-shrink-0 rounded-sm {{ $priorityClass }}"></span>
        @else
            <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full {{ $priorityClass }}"></span>
        @endif
    @endif

    @if ($icon === 'hourglass')
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M7 3H17" stroke-width="2" stroke-linecap="round" />
            <path d="M7 21H17" stroke-width="2" stroke-linecap="round" />
            <path d="M8 3C8 7 10.5 8.5 12 10C13.5 11.5 16 13 16 17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M16 3C16 7 13.5 8.5 12 10C10.5 11.5 8 13 8 17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    @elseif($icon === 'lock')
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <rect x="5" y="11" width="14" height="10" rx="2" stroke-width="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke-width="2" />
        </svg>
    @endif

    <span class="{{ $resolvedNameClass }}">
        {{ $displayName }}
    </span>
</span>

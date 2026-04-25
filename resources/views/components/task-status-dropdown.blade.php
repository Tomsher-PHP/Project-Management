@props([
    'task',
    'statuses' => [],
    'canChange' => false,
    'transitionUrl' => null,
    'includeTaskDetail' => false,
])

@php
    $statuses = collect($statuses);
    $statusColor = $task->status?->color ?: '#94A3B8';
    $statusName = $task->status?->name ?? 'No Status';
@endphp

@if ($canChange && filled($transitionUrl) && $statuses->isNotEmpty())
    <div class="relative min-w-[150px] shrink-0 sm:min-w-[165px]" data-task-status-dropdown>
        <button
            type="button"
            class="relative flex h-[42px] w-[150px] items-center justify-between rounded-lg px-4 text-sm font-semibold text-white shadow-sm transition duration-200 sm:w-[165px]"
            data-task-status-trigger
            style="border: 1px solid {{ $statusColor }}; background-color: {{ $statusColor }};"
        >
            <span class="truncate whitespace-nowrap">{{ $statusName }}</span>
            <span>
                <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-white">
                    <path d="M5.58203 8.3186L10.582 13.3186L15.582 8.3186" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </button>

        <div class="absolute right-0 top-14 z-20 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500" data-task-status-menu>
            <ul class="max-h-72 overflow-y-auto">
                @foreach ($statuses as $statusOption)
                    @php
                        $isCurrent = (int) ($task->status_id ?? 0) === (int) $statusOption->id;
                    @endphp

                    <li>
                        <button
                            type="button"
                            class="flex w-full items-center justify-between px-5 py-2 text-left text-sm font-semibold text-bgray-900 transition hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600"
                            data-task-status-option
                            data-task-id="{{ $task->id }}"
                            data-status-id="{{ $statusOption->id }}"
                            data-transition-url="{{ $transitionUrl }}"
                            data-current-status-id="{{ $task->status_id ?? '' }}"
                            data-include-task-detail="{{ $includeTaskDetail ? 'true' : 'false' }}"
                        >
                            <span class="flex items-center gap-2 {{ $isCurrent ? 'text-success-400 dark:text-success-300' : '' }}">
                                <span class="inline-flex h-3 w-3 rounded-full" style="background-color: {{ $statusOption->color ?: '#9CA3AF' }}"></span>
                                <span>{{ $statusOption->name }}</span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@else
    <span class="inline-flex h-[42px] items-center whitespace-nowrap rounded-full px-4 text-sm font-semibold text-white" style="border: 1px solid {{ $statusColor }}; background-color: {{ $statusColor }};">
        {{ $statusName }}
    </span>
@endif

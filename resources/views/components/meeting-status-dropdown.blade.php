@props(['meeting', 'statuses' => [], 'canChange' => false, 'updateUrl' => null])

@php
    $statuses = collect($statuses);
    $statusColor = $meeting->meetingStatus?->color ?: '#6B7280';
    $statusName = $meeting->meetingStatus?->name ?? 'No Status';
@endphp

@if ($canChange && filled($updateUrl) && $statuses->isNotEmpty())
    <div class="relative inline-block" data-meeting-status-dropdown>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold shadow-sm transition-all duration-200 hover:opacity-90" data-meeting-status-trigger style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
            <span class="inline-flex h-2 w-2 rounded-full" style="background-color: {{ $statusColor }};"></span>
            <span data-meeting-status-label class="truncate">Status: {{ $statusName }}</span>
            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" class="transition-transform duration-200">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <div class="absolute left-0 top-full mt-1.5 z-30 hidden min-w-[160px] overflow-hidden rounded-xl bg-white p-1 shadow-lg ring-1 ring-black/5 dark:bg-darkblack-500 dark:ring-white/10" data-meeting-status-menu>
            <ul class="max-h-60 overflow-y-auto py-1">
                @foreach ($statuses as $statusOption)
                    @php
                        $isCurrent = (int) ($meeting->meeting_status_id ?? 0) === (int) $statusOption->id;
                        $optColor = $statusOption->color ?: '#6B7280';
                    @endphp

                    <li>
                        <button type="button" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-semibold transition hover:bg-bgray-100 dark:hover:bg-darkblack-400 {{ $isCurrent ? 'bg-bgray-100 text-bgray-900 dark:bg-darkblack-400 dark:text-white' : 'text-bgray-700 dark:text-bgray-300' }}" data-meeting-status-option data-meeting-id="{{ $meeting->id }}" data-status-id="{{ $statusOption->id }}" data-update-url="{{ $updateUrl }}" data-current-status-id="{{ $meeting->meeting_status_id ?? '' }}">
                            <span class="flex items-center gap-2">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full" style="background-color: {{ $optColor }}"></span>
                                <span>{{ $statusOption->name }}</span>
                            </span>
                            @if ($isCurrent)
                                <svg class="h-4 w-4 text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@else
    @if ($meeting->meetingStatus)
        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
            <span class="inline-flex h-2 w-2 rounded-full" style="background-color: {{ $statusColor }};"></span>
            Status: {{ $statusName }}
        </span>
    @endif
@endif

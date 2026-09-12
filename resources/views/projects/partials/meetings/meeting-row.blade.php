@php
    $dateFormat = config('constants.date_format', 'd M Y');
    $timeFormat = config('constants.time_format', 'H:i');
    $typeColor = $meeting->meetingType?->color ?: '#3B82F6';
    $statusColor = $meeting->meetingStatus?->color ?: '#6B7280';
    $startTimeStr = $meeting->start_at ? $meeting->start_at->format($dateFormat . ' • ' . $timeFormat) : 'N/A';
    $endTimeStr = $meeting->end_at ? $meeting->end_at->format($timeFormat) : '';
    $durationStr = $meeting->start_at && $meeting->end_at ? $meeting->start_at->diffForHumans($meeting->end_at, true) : '';
@endphp

<tr class="transition hover:bg-bgray-50/70 dark:hover:bg-darkblack-500/60 cursor-pointer preview-meeting-btn" data-id="{{ $meeting->id }}" data-project-meeting-id="{{ $meeting->id }}">
    <!-- Meeting Title & Badges -->
    <td class="border border-bgray-200 px-4 py-3 align-top dark:border-darkblack-400">
        <div class="min-w-0 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                @if ($meeting->meetingType)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" style="background-color: {{ $typeColor }}20; color: {{ $typeColor }}; border: 1px solid {{ $typeColor }}40;">
                        {{ $meeting->meetingType->name }}
                    </span>
                @endif
                @if ($meeting->meetingStatus)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                        {{ $meeting->meetingStatus->name }}
                    </span>
                @endif
            </div>
            <h4 class="text-sm font-bold text-bgray-900 dark:text-white truncate" title="{{ $meeting->title }}">
                {{ limitStringChar($meeting->title, 50, '..') }}
            </h4>
        </div>
    </td>

    <!-- Date & Time -->
    <td class="whitespace-nowrap border-b border-r border-bgray-200 px-4 py-3 align-top text-xs text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
        <div class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $startTimeStr }}
            @if ($endTimeStr)
                - {{ $endTimeStr }}
            @endif
        </div>
        @if ($durationStr)
            <div class="mt-0.5 text-[11px] text-bgray-700 dark:text-bgray-300">
                ({{ $durationStr }})
            </div>
        @endif
    </td>

    <!-- Organizer -->
    <td class="whitespace-nowrap border-b border-r border-bgray-200 px-4 py-3 align-top text-xs text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
        @if ($meeting->organizer)
            <div class="flex items-center gap-2">
                <x-user-avatar :name="$meeting->organizer->name" :image="$meeting->organizer->profile_image_url ?: null" size="sm" />
                <span class="font-semibold text-bgray-700 dark:text-bgray-300">{{ $meeting->organizer->name }}</span>
            </div>
        @else
            <span class="text-bgray-700 dark:text-bgray-300">--</span>
        @endif
    </td>

    <!-- Actions -->
    <td class="whitespace-nowrap border-b border-bgray-200 px-4 py-3 text-right align-top dark:border-darkblack-400">
        <button type="button" class="preview-meeting-btn inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-id="{{ $meeting->id }}" title="View Meeting Details">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </button>
    </td>
</tr>

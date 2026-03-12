<td class="border border-gray-300 px-4 py-2 text-center">
    {{-- VIEW MODE + Edit Button --}}
    <div class="relative group">

        {{-- Shift info --}}
        <div class="shift-view flex items-center justify-center gap-2">
            @if ($shift)
                <div class="flex flex-col items-center rounded px-2 py-1 text-sm font-medium min-w-[80px]" style="background-color: {{ $bg }}; color: {{ $text }}">
                    <span>{{ $shift->shift_name }}</span>
                    @if ($timeFrom && $timeTo)
                        <span>{{ $timeFrom }} - {{ $timeTo }}</span>
                    @endif
                </div>
            @else
                <span class="text-gray-400">--</span>
            @endif
        </div>

        {{-- Edit icon (hidden by default, visible on hover) --}}
        @unless ($isPast)
            <button class="edit-shift open-shift-modal absolute top-1 right-1 opacity-0 group-hover:opacity-100 text-gray-500 hover:text-blue-600 transition rounded-full p-1 bg-white dark:bg-darkblack-600 shadow-sm" data-user="{{ $user->id }}" data-date="{{ $dateStr }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                </svg>
            </button>
        @endunless

    </div>
</td>

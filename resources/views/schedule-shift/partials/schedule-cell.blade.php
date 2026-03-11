<td class="px-2 py-2 text-center">

    {{-- VIEW MODE --}}
    <div class="shift-view flex items-center justify-center gap-2">

        @if ($shift)
            <div class="flex flex-col items-center rounded px-2 py-1 text-sm font-medium" style="background-color: {{ $bg }}; color: {{ $text }}">
                <span>{{ $shift->shift_name }}</span>
                @if ($timeFrom && $timeTo)
                    <span>{{ $timeFrom }} - {{ $timeTo }}</span>
                @endif
            </div>
        @else
            <span class="text-gray-400">--</span>
        @endif

    </div>

    {{-- EDIT MODE --}}
    @unless ($isPast)
        <div class="shift-edit hidden mt-1">
            <select class="shift-select w-full border rounded" data-user="{{ $user->id }}" data-date="{{ $dateStr }}">

                <option value="">--</option>

                @foreach ($shifts as $shiftOption)
                    <option value="{{ $shiftOption->id }}" @selected($shift?->shift_id == $shiftOption->id)>
                        {{ $shiftOption->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endunless

    {{-- Edit icon always stable, but disabled for past --}}
    @if (!$isPast)
        <button class="edit-shift group text-gray-500 hover:text-blue-600" data-user="{{ $user->id }}" data-date="{{ $dateStr }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
            </svg>
        </button>
    @endif

</td>

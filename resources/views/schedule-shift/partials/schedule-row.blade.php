<tr class="border-t border-gray-200 dark:border-darkblack-400 hover:bg-gray-50 dark:hover:bg-darkblack-500">
    <!-- Checkbox column -->
    <td class="px-4 py-2 text-center">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="selected_users[]" value="{{ $user->id }}" class="user-checkbox h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">

            <span class="text-sm font-semibold text-gray-600 dark:text-bgray-50">
                {{ $user->name }}
            </span>
        </label>
    </td>

    <!-- Shifts for each day -->
    @foreach ($weekDates as $date)
        @php
            $dateStr = $date->toDateString();
            $shift = $calendar[$user->id][$dateStr] ?? null;

            $isPast = $date->isBefore(today());
            $bg = $shift->color_code ?? '#e5e7eb';
            $text = '#000';

            $timeFrom = $shift ? \Carbon\Carbon::parse($shift->time_from)->format('h:i A') : null;
            $timeTo = $shift ? \Carbon\Carbon::parse($shift->time_to)->format('h:i A') : null;
        @endphp

        @include('schedule-shift.partials.schedule-cell')
    @endforeach

</tr>

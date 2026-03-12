@if ($assignments->isEmpty())

    <div class="text-green-500">
        No existing schedules found.
    </div>
@else
    <div class="space-y-3">
        @foreach ($assignments as $userId => $userAssignments)
            <div class="border rounded-lg p-4 bg-gray-50 dark:bg-darkblack-600 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <div class="font-semibold text-gray-800 dark:text-white text-lg">
                        {{ $userAssignments->first()->user->name }}
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="checkbox"
                            class="remove-user-checkbox h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600"
                            value="{{ $userId }}">
                        Remove User
                    </label>
                </div>

                <div class="space-y-2">
                    @foreach ($userAssignments as $assignment)
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between bg-white dark:bg-darkblack-500 border border-gray-200 dark:border-darkblack-400 rounded-lg p-3 shadow-sm hover:shadow-md transition">
                            <div class="flex items-center gap-2 mb-1 md:mb-0">
                                <span
                                    class="block rounded-sm bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">
                                    {{ $assignment->shift_name ?? 'None' }}
                                    ({{ $assignment->time_from_formatted ?? '' }} -
                                    {{ $assignment->time_to_formatted ?? '' }})
                                </span>
                            </div>

                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($assignment->date_from)->format('d M Y') }} -
                                {{ $assignment->date_to ? \Carbon\Carbon::parse($assignment->date_to)->format('d M Y') : '...' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif

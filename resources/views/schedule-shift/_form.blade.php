<form action="{{ route('schedule.shift.store') }}" method="POST">
    @csrf

    {{-- <div class="grid grid-cols-1 gap-6 md:grid-cols-2"> --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 pb-4 mb-6">

        <!-- Users -->
        <div class="flex flex-col gap-2">
            <label for="user-select" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                Select Users
            </label>

            <select name="users[]" multiple id="user-select" class="tom-select-multiple w-full">
                <option value="">Select Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('users')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Shift -->
        <div class="flex flex-col gap-2">
            <label for="shift_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                Shift
            </label>

            <select name="shift_id" id="shift_id" class="select-subtypes w-full" data-sort="0">

                <option value="">Select Shift</option>

                @foreach ($shifts as $shift)
                    <option value="{{ $shift->id }}" data-subtype="{{ $shift->time_from_formatted . ' - ' . $shift->time_to_formatted }}">
                        {{ $shift->name }}
                    </option>
                @endforeach

            </select>

            @error('shift_id')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Date From -->
        <div class="flex flex-col gap-2">
            <label for="date_from" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                Date From
            </label>

            <input type="date" name="date_from" id="date_from" data-format="{{ config('constants.date_format') }}" data-min-date="today" class="datepicker w-full rounded-lg border border-gray-300 p-2 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

            @error('date_from')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Date To -->
        <div class="flex flex-col gap-2">
            <label for="date_to" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                Date To
            </label>

            <input type="date" name="date_to" id="date_to" data-format="{{ config('constants.date_format') }}" data-min-date="today" class="datepicker w-full rounded-lg border border-gray-300 p-2 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

            @error('date_to')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Reason -->
        <div class="flex flex-col gap-2 md:col-span-2">
            <label for="reason" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                Reason
            </label>

            <textarea name="reason" id="reason" rows="3" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400"></textarea>

            @error('reason')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

    </div>

    <!-- Submit -->
    <div class="pt-6 border-t flex justify-end dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
        <button type="submit" class="px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition">
            Create Schedule
        </button>
    </div>

</form>

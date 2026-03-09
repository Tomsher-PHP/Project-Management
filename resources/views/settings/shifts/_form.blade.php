<form action="{{ isset($shift) ? route('settings.shifts.update', $shift->id) : route('settings.shifts.store') }}" method="POST" class="space-y-10">
    @csrf
    @if (isset($shift))
        @method('PUT')
    @endif

    {{-- ================= BASIC ROLE INFORMATION ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Shift Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            {{-- Shift Name --}}
            <div class="flex flex-col gap-2">
                <label for="name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Shift Name
                </label>

                <input type="text" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" class="w-full rounded-lg border border-gray-300 p-2
                              focus:border focus:border-success-300 focus:ring-0
                              dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                              @error('name') border border-red-500 @enderror">

                <input type="hidden" name="role_id" value="{{ $role->id ?? '' }}">

                @error('name')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- User Type --}}
            <div class="flex flex-col gap-2">
                <label for="color_code" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Color
                </label>

                <input type="color" name="color_code" id="color_code" value="{{ old('color_code', $shift['color_code'] ?? '#6b7280') }}" class="w-16 h-10 p-0 border rounded cursor-pointer focus:outline-none focus:ring-2 focus:ring-success-300">

                @error('color_code')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </div>
    </div>

    {{-- ================= Shift Information ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Time Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Start Time</label>
                <input type="text" name="start_time" data-mode="12" value="{{ old('start_time', $shift['start_time'] ?? '09:00') }}" class="timepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">

                @error('start_time')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">End Time</label>
                <input type="text" name="end_time" data-mode="12" value="{{ old('end_time', $shift['end_time'] ?? '18:00') }}" class="timepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">

                @error('end_time')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Break Duration
                </label>

                <select name="break_duration" class="select-no-search w-full @error('user_type') border border-red-500 @enderror">

                    @php
                        $breaks = [0, 15, 30, 45, 60, 90, 120];
                    @endphp

                    @foreach ($breaks as $minutes)
                        <option value="{{ $minutes }}" {{ old('break_duration', $shift['break_duration'] ?? 60) == $minutes ? 'selected' : '' }}>
                            {{ $minutes }} minutes
                        </option>
                    @endforeach

                </select>

                @error('break_duration')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror

            </div>

        </div>

        <div class="mt-8">
            <h4 class="text-lg font-semibold text-gray-800 mb-4 dark:text-white">
                Mark Weekend Days
            </h4>

            @php
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            @endphp

            <div class="space-y-4">
                @foreach ($days as $dayKey => $dayLabel)
                    @php
                        $daySlug = strtolower($dayLabel);
                        $oldWeeks = old("weekend_days.$dayKey", []); // get old input
                    @endphp

                    <div class="flex items-center gap-6">

                        {{-- Day label --}}
                        <label class="flex items-center gap-2 w-28 cursor-pointer">
                            <span class="font-medium text-gray-700 dark:text-bgray-50">
                                {{ $dayLabel }}
                            </span>
                        </label>

                        {{-- Select All Day --}}
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="day-toggle h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600" data-day="{{ $daySlug }}" {{ $dayLabel === 'Sunday' && empty($oldWeeks) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-600 dark:text-bgray-50">
                                All
                            </span>
                        </label>

                        {{-- Weeks --}}
                        <div class="flex gap-4">
                            @for ($week = 1; $week <= 5; $week++)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="weekend_days[{{ $dayKey }}][]" value="{{ $week }}" class="week-checkbox {{ $daySlug . '_check' }} h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600" {{ in_array($week, $oldWeeks) || ($dayLabel === 'Sunday' && empty($oldWeeks)) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-600 dark:text-bgray-50">
                                        W{{ $week }}
                                    </span>
                                </label>
                            @endfor
                        </div>

                    </div>
                @endforeach
            </div>

            @error('weekend_days')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror

        </div>

    </div>

    {{-- Submit Button --}}
    <div class="pt-6 border-t flex justify-end dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
        <button type="submit" class="px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition">
            @if (isset($shift))
                Update Shift
            @else
                Create Shift
            @endif
        </button>
    </div>

</form>

@push('scripts')
    <script>
        document.querySelectorAll('.day-toggle').forEach(toggle => {

            toggle.addEventListener('change', function() {

                let day = this.dataset.day;
                let checked = this.checked;

                document.querySelectorAll(`.${day}_check`).forEach(box => {
                    box.checked = checked;
                });

            });

        });
    </script>
@endpush

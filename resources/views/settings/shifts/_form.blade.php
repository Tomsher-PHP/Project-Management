<form action="{{ isset($shift) ? route('settings.shifts.update', $shift->id) : route('settings.shifts.store') }}" method="POST" class="space-y-10">
    @csrf
    @if (isset($shift))
        @method('PUT')
    @endif

    @php
        $isDisabled = isset($shift);
    @endphp

    <!-- ================= BASIC INFORMATION ================= -->
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Shift Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            <!-- Shift Name -->
            <div class="flex flex-col gap-2">
                <label for="name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Shift Name <x-red-star />
                </label>

                <input type="text" id="name" name="name" value="{{ old('name', $shift->name ?? '') }}" class="w-full rounded-lg border border-gray-300 p-2
                              focus:border focus:border-success-300 focus:ring-0
                              dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                              @error('name') border border-red-500 @enderror">

                @error('name')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Color -->
            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Color
                </label>

                @php
                    $colors = config('constants.soft_colors');
                    $selectedColor = old('color_code', $shift->color_code ?? '');
                @endphp

                <div class="flex flex-wrap gap-3 pt-1">
                    @foreach ($colors as $color)
                        <label class="relative cursor-pointer group">

                            <input type="radio" name="color_code" value="{{ $color }}" class="peer absolute opacity-0 w-0 h-0" @checked($selectedColor === $color)>

                            <span class="block w-10 h-10 rounded-md border border-gray-300
                           transition transform group-hover:scale-110
                           peer-checked:ring-2
                           peer-checked:ring-success-400
                           peer-checked:border-success-400" style="background-color: {{ $color }}">
                            </span>

                            <span class="absolute inset-0 flex items-center justify-center
                           text-white text-xl pointer-events-none
                           opacity-0 peer-checked:opacity-100" style="color: black">
                                ✓
                            </span>

                        </label>
                    @endforeach
                </div>

                @error('color_code')
                    <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    <!-- ================= Shift Information ================= -->
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Time Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Start Time <x-red-star /></label>
                <input type="text" name="start_time" data-mode="12" value="{{ old('start_time', $shift?->time_from->format('H:i') ?? '09:00') }}" class="timepicker w-full rounded-lg border p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:disabled:border-darkblack-400 dark:disabled:bg-darkblack-600 dark:disabled:text-bgray-400 disabled:cursor-not-allowed disabled:border-bgray-200 disabled:text-bgray-700 @if($isDisabled) bg-bgray-200 @else bg-white @endif @error('start_time') border-red-500 @else border-gray-300 dark:border-darkblack-400 @enderror" @disabled($isDisabled)>

                @error('start_time')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">End Time <x-red-star /></label>
                <input type="text" name="end_time" data-mode="12" value="{{ old('end_time', $shift?->time_to->format('H:i') ?? '18:00') }}" class="timepicker w-full rounded-lg border p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:disabled:border-darkblack-400 dark:disabled:bg-darkblack-600 dark:disabled:text-bgray-400 disabled:cursor-not-allowed disabled:border-bgray-200 disabled:text-bgray-700 @if($isDisabled) bg-bgray-200 @else bg-white @endif @error('end_time') border-red-500 @else border-gray-300 dark:border-darkblack-400 @enderror" @disabled($isDisabled)>

                @error('end_time')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Break Duration (Minutes) <x-red-star />
                </label>

                @php
                    $shiftBreakMin = ($shift?->break_duration ?? 3600) / 60;
                @endphp

                <input type="text" name="break_duration" value="{{ old('break_duration', $shiftBreakMin ?? '60') }}" class="w-full rounded-lg border p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:disabled:border-darkblack-400 dark:disabled:bg-darkblack-600 dark:disabled:text-bgray-400 disabled:cursor-not-allowed disabled:border-bgray-200 disabled:text-bgray-700 @if($isDisabled) bg-bgray-200 @else bg-white @endif @error('break_duration') border-red-500 @else border-gray-300 dark:border-darkblack-400 @enderror" @disabled($isDisabled)>

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

                // Prepare existing weekends for edit form
                $existingWeekends = [];
                if (isset($shift) && $shift->weekends) {
                    foreach ($shift->weekends as $weekend) {
                        $existingWeekends[$weekend->weekday][] = $weekend->week_number;
                    }
                }
            @endphp

            <div class="space-y-4">
                @foreach ($days as $dayKey => $dayLabel)
                    @php
                        $daySlug = strtolower($dayLabel);

                        // Check for old input first
                        $oldWeeks = old("weekend_days.$dayKey");

                        // If no old input:
                        if ($oldWeeks === null) {
                            if (isset($shift)) {
                                // Edit page → use existing saved weekends
                                $oldWeeks = $existingWeekends[$dayKey] ?? [];
                            } else {
                                // Create page → default Sunday all weeks
                                $oldWeeks = $dayLabel === 'Sunday' ? [1, 2, 3, 4, 5] : [];
                            }
                        }
                    @endphp

                    <div class="flex items-center gap-6">

                        <!-- Day label -->
                        <label class="flex items-center gap-2 w-28 cursor-pointer">
                            <span class="font-medium text-gray-700 dark:text-bgray-50">
                                {{ $dayLabel }}
                            </span>
                        </label>

                        <!-- Select All Day -->
                        <label class="flex items-center gap-2 {{ $isDisabled ? 'cursor-not-allowed opacity-70' : 'cursor-pointer' }}">
                            <input type="checkbox" class="day-toggle h-5 w-5 rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 disabled:cursor-not-allowed disabled:opacity-50 {{ $isDisabled ? '' : 'cursor-pointer' }}" data-day="{{ $daySlug }}" {{ count($oldWeeks) === 5 ? 'checked' : '' }} @disabled($isDisabled)>
                            <span class="text-sm text-gray-600 dark:text-bgray-50">
                                All
                            </span>
                        </label>

                        <!-- Weeks -->
                        <div class="flex gap-4">
                            @for ($week = 1; $week <= 5; $week++)
                                <label class="flex items-center gap-2 {{ $isDisabled ? 'cursor-not-allowed opacity-70' : 'cursor-pointer' }}">
                                    <input type="checkbox" name="weekend_days[{{ $dayKey }}][]" value="{{ $week }}" class="week-checkbox {{ $daySlug . '_check' }} h-5 w-5 rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 disabled:cursor-not-allowed disabled:opacity-50 {{ $isDisabled ? '' : 'cursor-pointer' }}" {{ in_array($week, $oldWeeks) ? 'checked' : '' }} @disabled($isDisabled)>
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

            @error('weekend_days')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror

        </div>

    </div>

    <!-- Submit Button -->
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

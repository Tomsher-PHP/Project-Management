<div class="modal fixed inset-0 z-50 overflow-y-auto modal-form block" id="initialShiftModal" style="z-index: 1050 !important;">
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-2xl">
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h3 class="modal-title text-2xl font-semibold text-bgray-900 dark:text-white">
                            Initial Shift Assignment
                        </h3>
                        <p class="text-sm text-bgray-600 dark:text-bgray-300 mt-1">
                            Assign a starting work schedule for <span class="font-bold text-success-400">{{ $user->name }}</span>
                        </p>
                    </div>

                    <a href="{{ route('users.index') }}" class="modal-close inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" title="Skip">
                        ✕
                    </a>
                </div>

                <form id="initial-shift-form" class="flex max-h-[80vh] flex-col" action="{{ route('users.initial-shift.store', $user->id) }}" method="POST">
                    @csrf

                    <div class="overflow-y-auto px-6 py-6 sm:px-7">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                            <!-- Shift -->
                            <div class="flex flex-col gap-2 md:col-span-2">
                                <label for="initial_shift_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                    Shift <x-red-star />
                                </label>

                                <select name="shift_id" id="initial_shift_id" class="tom-select w-full" required>
                                    <option value="">Select Shift</option>
                                    @foreach ($shifts as $shift)
                                        <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                            {{ $shift->name }} ({{ $shift->time_from_formatted }} - {{ $shift->time_to_formatted }})
                                        </option>
                                    @endforeach
                                </select>

                                @error('shift_id')
                                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Start Date -->
                            <div class="flex flex-col gap-2">
                                <label for="initial_date_from" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                    Start Date <x-red-star />
                                </label>

                                <input type="date" name="date_from" value="{{ old('date_from', $user->created_at ? $user->created_at->format('Y-m-d') : now()->format('Y-m-d')) }}" id="initial_date_from" placeholder="YYYY-MM-DD" class="datepicker w-full rounded-lg border border-gray-300 p-2 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>

                                @error('date_from')
                                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- End Date -->
                            <div class="flex flex-col gap-2">
                                <label for="initial_date_to" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                    End Date
                                </label>

                                <input type="date" name="date_to" value="{{ old('date_to') }}" id="initial_date_to" placeholder="YYYY-MM-DD" class="datepicker w-full rounded-lg border border-gray-300 p-2 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                @error('date_to')
                                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Reason -->
                            <div class="flex flex-col gap-2 md:col-span-2">
                                <label for="initial_reason" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                    Reason
                                </label>

                                <textarea name="reason" id="initial_reason" rows="2" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" placeholder="Optional notes for this initial schedule">{{ old('reason', 'Initial onboarding schedule') }}</textarea>

                                @error('reason')
                                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <a href="{{ route('users.index') }}" class="rounded-lg border border-bgray-300 bg-white px-4 py-2 text-sm text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white">
                            Skip Assignment
                        </a>

                        <button type="submit" class="rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-success-400">
                            Assign Shift
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

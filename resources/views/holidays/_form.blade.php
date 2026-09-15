@php
    $isEdit = isset($holiday);

    $selectedUserIds = collect(
        old(
            'user_ids',
            $selectedUserIds ?? ($holiday?->users?->pluck('id')->toArray() ?? [])
        )
    )
        ->map(fn ($id) => (int) $id)
        ->values()
        ->toArray();

    $selectedShiftIds = collect(
        old(
            'shift_ids',
            $selectedShiftIds ?? ($holiday?->shifts?->pluck('id')->toArray() ?? [])
        )
    )
        ->map(fn ($id) => (int) $id)
        ->values()
        ->toArray();

    $appliedTo = old(
        'applied_to',
        $holiday->applied_to ?? 'all_users'
    );
@endphp

<form
    action="{{ $isEdit ? route('holidays.update', $holiday->id) : route('holidays.store') }}"
    method="POST"
>
    @csrf

    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- ================= HOLIDAY INFORMATION ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Holiday Information
        </h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Holiday Name --}}
            <div class="flex flex-col gap-2">
                <label
                    for="holiday_name"
                    class="text-base font-medium text-bgray-600 dark:text-bgray-50"
                >
                    Holiday Name <x-red-star />
                </label>

                <input
                    type="text"
                    name="name"
                    id="holiday_name"
                    value="{{ old('name', $holiday->name ?? '') }}"
                    placeholder="Enter holiday name"
                    class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400 @error('name') border-red-500 focus:border-red-500 @enderror"
                >

                @error('name')
                    <p class="mt-1 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Public Holiday --}}
            <div>
                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                    Public Holiday
                </label>

                <label class="inline-flex items-center gap-2 mt-2">
                    <input
                        type="checkbox"
                        name="is_public"
                        value="1"
                        @checked(old('is_public', $holiday->is_public ?? false))
                        class="rounded border-bgray-300"
                    >

                    <span class="text-sm text-bgray-700 dark:text-white">
                        This is a public holiday
                    </span>
                </label>

                @error('is_public')
                    <p class="mt-1 text-xs text-red-500">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- From Date --}}
            <div class="flex flex-col gap-2">
                <label
                    for="from_date"
                    class="text-base font-medium text-bgray-600 dark:text-bgray-50"
                >
                    From Date <x-red-star />
                </label>

                <input
                    type="date"
                    name="from_date"
                    id="from_date"
                    value="{{ old('from_date', isset($holiday) && $holiday->from_date ? $holiday->from_date->format('Y-m-d') : '') }}"
                    class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400 @error('from_date') border-red-500 focus:border-red-500 @enderror"
                >

                @error('from_date')
                    <p class="mt-1 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- To Date --}}
            <div class="flex flex-col gap-2">
                <label
                    for="to_date"
                    class="text-base font-medium text-bgray-600 dark:text-bgray-50"
                >
                    To Date <x-red-star />
                </label>

                <input
                    type="date"
                    name="to_date"
                    id="to_date"
                    value="{{ old('to_date', isset($holiday) && $holiday->to_date ? $holiday->to_date->format('Y-m-d') : '') }}"
                    class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400 @error('to_date') border-red-500 focus:border-red-500 @enderror"
                >

                @error('to_date')
                    <p class="mt-1 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </div>
    </div>


    {{-- ================= HOLIDAY APPLICATION ================= --}}
    <div class="mt-8">

        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Holiday Application
        </h3>

        <div class="grid grid-cols-1 gap-6">

            {{-- Applied To --}}
            <div class="flex flex-col gap-2">

                <label
                    for="applied_to"
                    class="text-base font-medium text-bgray-600 dark:text-bgray-50"
                >
                    Applied To <x-red-star />
                </label>

                <select
                    name="applied_to"
                    id="applied_to"
                    class="tom-select-no-search w-full @error('applied_to') border-red-500 @enderror"
                >
                    <option value="all_users" @selected($appliedTo === 'all_users')>
                        All Users
                    </option>

                    <option value="shift" @selected($appliedTo === 'shift')>
                        Specific Shift(s)
                    </option>

                    <option value="user" @selected($appliedTo === 'user')>
                        Specific User(s)
                    </option>
                </select>

                @error('applied_to')
                    <p class="mt-1 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- ================= USERS ================= --}}
            <div
                id="holiday-users-wrapper"
                class="{{ $appliedTo === 'user' ? '' : 'hidden' }}"
            >
                <div class="flex flex-col gap-2">

                    <label
                        for="user_ids"
                        class="text-base font-medium text-bgray-600 dark:text-bgray-50"
                    >
                        Select Users <x-red-star />
                    </label>

                    <select
                        name="user_ids[]"
                        id="user_ids"
                        class="tom-select-multiple w-full"
                        multiple
                    >
                        @foreach ($users as $user)
                            <option
                                value="{{ $user->id }}"
                                @selected(in_array((int) $user->id, $selectedUserIds, true))
                            >
                                {{ $user->name }}{{ $user->is_active ? '' : ' (Inactive)' }}
                            </option>
                        @endforeach
                    </select>

                    @error('user_ids')
                        <p class="mt-1 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('user_ids.*')
                        <p class="mt-1 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror

                </div>
            </div>


            {{-- ================= SHIFTS ================= --}}
            <div
                id="holiday-shifts-wrapper"
                class="{{ $appliedTo === 'shift' ? '' : 'hidden' }}"
            >
                <div class="flex flex-col gap-2">

                    <label
                        for="shift_ids"
                        class="text-base font-medium text-bgray-600 dark:text-bgray-50"
                    >
                        Select Shift(s) <x-red-star />
                    </label>

                    <select
                        name="shift_ids[]"
                        id="shift_ids"
                        class="tom-select-multiple w-full"
                        multiple
                    >
                        @foreach ($shifts as $shift)
                            <option
                                value="{{ $shift->id }}"
                                @selected(in_array((int) $shift->id, $selectedShiftIds, true))
                            >
                                {{ $shift->name }}{{ $shift->is_active ? '' : ' (Inactive)' }}
                            </option>
                        @endforeach
                    </select>

                    @error('shift_ids')
                        <p class="mt-1 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('shift_ids.*')
                        <p class="mt-1 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror

                </div>
            </div>


            {{-- Application Information --}}
            <div
                id="holiday-application-info"
                class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-900"
            >
                <span id="holiday-application-info-text"></span>
            </div>

        </div>
    </div>


    {{-- ================= DESCRIPTION ================= --}}
    <div class="mt-8">

        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Additional Information
        </h3>

        <div class="flex flex-col gap-2">

            <label
                for="holiday_description"
                class="text-base font-medium text-bgray-600 dark:text-bgray-50"
            >
                Description
            </label>

            <textarea
                name="description"
                id="holiday_description"
                rows="5"
                placeholder="Enter holiday description"
                class="w-full rounded-lg border border-gray-300 p-3 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400 @error('description') border-red-500 focus:border-red-500 @enderror"
            >{{ old('description', $holiday->description ?? '') }}</textarea>

            @error('description')
                <p class="mt-1 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror

        </div>
    </div>


    {{-- ================= STATUS ================= --}}
    <div class="mt-8">

        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Status
        </h3>

        <label class="inline-flex items-center gap-2">

            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $holiday->is_active ?? true))
                class="rounded border-bgray-300"
            >

            <span class="text-sm text-bgray-700 dark:text-white">
                Active
            </span>

        </label>

        @error('is_active')
            <p class="mt-1 text-xs text-red-500">
                {{ $message }}
            </p>
        @enderror

    </div>


    {{-- ================= SUBMIT ================= --}}
    <div class="pt-6 mt-8 border-t flex justify-end dark:border-darkblack-400">

        <button
            type="submit"
            class="px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition"
        >
            @if ($isEdit)
                Update Holiday
            @else
                Create Holiday
            @endif
        </button>

    </div>

</form>

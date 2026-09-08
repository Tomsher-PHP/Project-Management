@extends('layouts.master')

@section('page-content')

<div>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-bgray-900 dark:text-white">
                Add Holiday
            </h2>

            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                Add a holiday to the company holiday calendar.
            </p>
        </div>

        <a href="{{ route('holidays.index') }}"
           class="rounded-lg border border-bgray-200 px-4 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
            Back
        </a>
    </div>


    <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600">

        <form action="{{ route('holidays.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- Holiday Name --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        Holiday Name <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                        placeholder="Enter holiday name"
                    >

                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                {{-- From Date --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        From Date <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="from_date"
                        value="{{ old('from_date') }}"
                        required
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    >

                    @error('from_date')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                {{-- To Date --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        To Date <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        value="{{ old('to_date') }}"
                        required
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    >

                    @error('to_date')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Is Public --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        Public Holiday
                    </label>

                    <label class="inline-flex items-center gap-2 mt-2">
                        <input
                            type="checkbox"
                            name="is_public"
                            value="1"
                            {{ old('is_public', false) ? 'checked' : '' }}
                            class="rounded border-bgray-300"
                        >

                        <span class="text-sm text-bgray-700 dark:text-white">
                            This is a public holiday
                        </span>
                    </label>

                    @error('is_public')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Applied To --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        Applied To <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="applied_to"
                        id="applied_to"
                        required
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    >
                        <option value="">Select</option>

                        <option value="all_users"
                            {{ old('applied_to') == 'all_users' ? 'selected' : '' }}>
                            All Users
                        </option>

                        <option value="shift"
                            {{ old('applied_to') == 'shift' ? 'selected' : '' }}>
                            Shift-wise
                        </option>

                        <option value="user"
                            {{ old('applied_to') == 'user' ? 'selected' : '' }}>
                            User-wise
                        </option>
                    </select>

                    @error('applied_to')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Users --}}
                <div
                    id="users-wrapper"
                    class="md:col-span-2 hidden"
                >
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        Select Users <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="user_ids[]"
                        id="user_ids"
                        multiple
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    >
                        @foreach($users as $user)
                            <option
                                value="{{ $user->id }}"
                                {{ in_array($user->id, old('user_ids', [])) ? 'selected' : '' }}
                            >
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-1 text-xs text-bgray-500">
                        Hold Ctrl/Cmd to select multiple users.
                    </p>

                    @error('user_ids')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    @error('user_ids.*')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Shift --}}
                <div
                    id="shift-wrapper"
                    class="hidden"
                >
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        Select Shift <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="shift_id"
                        id="shift_id"
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    >
                        <option value="">Select Shift</option>

                        @foreach($shifts as $shift)
                            <option
                                value="{{ $shift->id }}"
                                {{ old('shift_id') == $shift->id ? 'selected' : '' }}
                            >
                                {{ $shift->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('shift_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Description --}}
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        Description
                    </label>

                    <textarea
                        name="description"
                        rows="4"
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                        placeholder="Enter description"
                    >{{ old('description') }}</textarea>

                    @error('description')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Active --}}
                <div class="md:col-span-2">

                    <label class="inline-flex items-center gap-2">

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', true) ? 'checked' : '' }}
                            class="rounded border-bgray-300"
                        >

                        <span class="text-sm text-bgray-700 dark:text-white">
                            Active
                        </span>

                    </label>

                </div>

            </div>


            <div class="mt-6 flex justify-end gap-3">

                <a href="{{ route('holidays.index') }}"
                   class="rounded-lg border border-bgray-200 px-5 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
                    Cancel
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-medium text-white hover:bg-success-400">
                    Save Holiday
                </button>

            </div>

        </form>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const appliedTo = document.getElementById('applied_to');
    const usersWrapper = document.getElementById('users-wrapper');
    const shiftWrapper = document.getElementById('shift-wrapper');

    const userSelect = document.getElementById('user_ids');
    const shiftSelect = document.getElementById('shift_id');

    function updateAppliedToFields() {

        usersWrapper.classList.add('hidden');
        shiftWrapper.classList.add('hidden');

        userSelect.removeAttribute('required');
        shiftSelect.removeAttribute('required');

        if (appliedTo.value === 'user') {

            usersWrapper.classList.remove('hidden');
            userSelect.setAttribute('required', 'required');

        } else if (appliedTo.value === 'shift') {

            shiftWrapper.classList.remove('hidden');
            shiftSelect.setAttribute('required', 'required');
        }
    }

    appliedTo.addEventListener('change', updateAppliedToFields);

    // Restore fields after validation error
    updateAppliedToFields();

});
</script>

@endsection

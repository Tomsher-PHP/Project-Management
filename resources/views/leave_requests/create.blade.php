@extends('layouts.master')

@section('page-content')


<div class="2xl:flex 2xl:space-x-[48px]">
    <section class="mb-6 2xl:mb-0 2xl:flex-1">

        <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">

            <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">

                <div class="col-span-12 w-full">

                    {{-- Page Header --}}
                    <div class="flex flex-row items-center gap-3 border-b border-bgray-200 pb-5">

                        <x-back-button />

                        <h3 class="text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                            Create New Leave Request
                        </h3>

                    </div>


                    {{-- Validation Errors --}}
                    @if ($errors->any())
                        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">

                            <ul class="list-disc pl-5">

                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach

                            </ul>

                        </div>
                    @endif


                    {{-- Leave Request Form --}}
                    <div class="mt-8">

                        <form
                            action="{{ route('leave-requests.store') }}"
                            method="POST"
                            enctype="multipart/form-data">

                            @csrf

                            <input
                                type="hidden"
                                name="user_id"
                                value="{{ auth()->id() }}">


                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">


                                {{-- Leave Type --}}

                                <div class="flex flex-col gap-2">
                                    <label for="leave_type_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Leave Type <x-red-star />
                                    </label>

                                    <select name="leave_type_id" id="leave_type_id" class="tom-select w-full">
                                        <option value="">Select Leave Type</option>
                                        @foreach ($leaveTypes as $leaveType)

                                            <option
                                                value="{{ $leaveType->id }}"
                                                data-file-required="{{ $leaveType->is_file_upload_required ? '1' : '0' }}"
                                                {{ old('leave_type_id') == $leaveType->id ? 'selected' : '' }}>

                                                {{ $leaveType->name }}

                                            </option>

                                        @endforeach
                                    </select>

                                    @error('leave_type_id')
                                        <p class="mt-2 text-sm text-error-300">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>


                                {{-- Type --}}
                                <div class="flex flex-col gap-2">
                                    <label for="type" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Type <x-red-star />
                                    </label>

                                    <select name="type" id="type" required class="tom-select-no-search w-full">
                                        <option value="">Select Type</option>

                                        <option
                                            value="full_day"
                                            {{ old('type', 'full_day') === 'full_day' ? 'selected' : '' }}>

                                            Full Day

                                        </option>

                                        <option
                                            value="half_day"
                                            {{ old('type') === 'half_day' ? 'selected' : '' }}>

                                            Half Day

                                        </option>

                                    </select>

                                    @error('type')
                                        <p class="mt-2 text-sm text-error-300">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                {{-- Leave Period --}}
                                <div
                                    id="leave-period-wrapper"
                                    class="hidden">

                                    <label
                                        for="half_day_type"
                                        class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">

                                        Leave Period <x-red-star />

                                    </label>

                                    <select
                                        name="half_day_type"
                                        id="half_day_type"
                                        class="tom-select-no-search w-full text-left">

                                        <option value="">
                                            Select Period
                                        </option>

                                        <option
                                            value="first_half"
                                            {{ old('half_day_type') === 'first_half' ? 'selected' : '' }}>

                                            First Half

                                        </option>

                                        <option
                                            value="second_half"
                                            {{ old('half_day_type') === 'second_half' ? 'selected' : '' }}>

                                            Second Half

                                        </option>

                                    </select>

                                    @error('half_day_type')
                                        <p class="mt-2 text-sm text-error-300">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                {{-- From Date --}}
                                <div class="flex flex-col gap-2">

                                    <label
                                        for="requested_from_date"
                                        class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">

                                        From Date <x-red-star />

                                    </label>

                                    <input
                                        type="date"
                                        name="requested_from_date"
                                        id="requested_from_date"
                                        value="{{ old('requested_from_date') }}"
                                        required
                                        class="datepicker w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                    @error('requested_from_date')
                                        <p class="mt-2 text-sm text-error-300">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                {{-- To Date --}}
                                <div class="flex flex-col gap-2">

                                    <label
                                        for="requested_to_date"
                                        class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">

                                        To Date <x-red-star />

                                    </label>

                                    <input
                                        type="date"
                                        name="requested_to_date"
                                        id="requested_to_date"
                                        value="{{ old('requested_to_date') }}"
                                        required
                                        class="datepicker w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                    @error('requested_to_date')
                                        <p class="mt-2 text-sm text-error-300">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                {{-- Number of Days --}}
                                <div class="flex flex-col gap-2">

                                    <label
                                        for="duration_display"
                                        class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">

                                        Number of Days

                                    </label>

                                    <input
                                        type="text"
                                        id="duration_display"
                                        value="0.00"
                                        readonly
                                        class="w-full rounded-lg border border-gray-300 bg-bgray-50 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                    <p class="mt-2 text-xs text-bgray-500">
                                        Automatically calculated.
                                    </p>

                                </div>


                                {{-- Attachment --}}
                                <div
                                    id="attachment-wrapper"
                                    class="hidden">

                                    <label
                                        for="attachment"
                                        class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">

                                        Supporting Document

                                        <span
                                            id="attachment-required"
                                            class="hidden text-red-500">

                                            *

                                        </span>

                                    </label>

                                    <input
                                        type="file"
                                        name="attachment"
                                        id="attachment"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                    <p
                                        id="attachment-help"
                                        class="mt-2 text-xs text-bgray-500">

                                        You may upload a supporting document if required.

                                    </p>

                                    @error('attachment')
                                        <p class="mt-2 text-sm text-error-300">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                {{-- Leave Balance Information --}}
                                <div
                                    id="leave-balance-info"
                                    class="hidden md:col-span-2">


                                    {{-- Balance Success --}}
                                    <div
                                        id="leave-balance-success"
                                        class="hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">

                                        <strong>
                                            Leave Balance:
                                        </strong>

                                        <span id="leave-balance-success-text"></span>

                                    </div>


                                    {{-- Balance Warning --}}
                                    <div
                                        id="leave-balance-warning"
                                        class="hidden rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">

                                        <div class="flex items-start gap-3">

                                            <svg
                                                class="mt-0.5 h-5 w-5 shrink-0"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24">

                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 9v2m0 4h.01M10.29 3.86l-7.1 12.28A2 2 0 004.93 19h14.14l-7.1-12.28a2 2 0 00-3.42 0z" />

                                            </svg>

                                            <div>

                                                <strong>
                                                    Leave Balance Notice
                                                </strong>

                                                <p
                                                    id="leave-balance-warning-text"
                                                    class="mt-1">
                                                </p>

                                                <p class="mt-2 text-xs">

                                                    You can still submit the request.
                                                    The final leave balance will be checked when the request is approved.

                                                </p>

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                {{-- Reason --}}
                                <div class="md:col-span-2">

                                    <label
                                        for="reason"
                                        class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">

                                        Reason <x-red-star />

                                    </label>

                                    <textarea
                                        name="reason"
                                        id="reason"
                                        rows="5"
                                        required
                                        placeholder="Enter the reason for your leave..."
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">{{ old('reason') }}</textarea>

                                    @error('reason')
                                        <p class="mt-2 text-sm text-error-300">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>

                            </div>


                            {{-- Form Buttons --}}
                            <div class="mt-8 flex justify-end gap-3 border-t border-bgray-200 pt-5">

                                <a
                                    href="{{ route('leave-requests.index') }}"
                                    class="rounded-lg border border-bgray-200 px-5 py-2.5 text-sm font-medium text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">

                                    Cancel

                                </a>

                                <button
                                    type="submit"
                                    class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-success-400">

                                    Submit Leave Request

                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </section>
</div>


@endsection

@push('scripts')


<style>
    /*
    |--------------------------------------------------------------------------
    | Tom Select Alignment
    |--------------------------------------------------------------------------
    */

    #leave_type_id + .ts-wrapper .ts-control,
    #type + .ts-wrapper .ts-control,
    #half_day_type + .ts-wrapper .ts-control {
        text-align: left !important;
    }

    #leave_type_id + .ts-wrapper .ts-control .placeholder,
    #leave_type_id + .ts-wrapper .ts-control .item,
    #type + .ts-wrapper .ts-control .placeholder,
    #type + .ts-wrapper .ts-control .item,
    #half_day_type + .ts-wrapper .ts-control .placeholder,
    #half_day_type + .ts-wrapper .ts-control .item {
        text-align: left !important;
    }
</style>


<script>
    document.addEventListener('DOMContentLoaded', function() {

        const leaveType =
            document.getElementById('leave_type_id');

        const type =
            document.getElementById('type');

        const fromDate =
            document.getElementById('requested_from_date');

        const toDate =
            document.getElementById('requested_to_date');

        const duration =
            document.getElementById('duration_display');


        /*
        |--------------------------------------------------------------------------
        | Leave Period
        |--------------------------------------------------------------------------
        */

        const leavePeriodWrapper =
            document.getElementById('leave-period-wrapper');

        const leavePeriod =
            document.getElementById('half_day_type');


        /*
        |--------------------------------------------------------------------------
        | Attachment
        |--------------------------------------------------------------------------
        */

        const attachmentWrapper =
            document.getElementById('attachment-wrapper');

        const attachment =
            document.getElementById('attachment');

        const attachmentRequired =
            document.getElementById('attachment-required');

        const attachmentHelp =
            document.getElementById('attachment-help');


        /*
        |--------------------------------------------------------------------------
        | Leave Balance
        |--------------------------------------------------------------------------
        */

        const leaveBalanceInfo =
            document.getElementById('leave-balance-info');

        const leaveBalanceSuccess =
            document.getElementById('leave-balance-success');

        const leaveBalanceSuccessText =
            document.getElementById('leave-balance-success-text');

        const leaveBalanceWarning =
            document.getElementById('leave-balance-warning');

        const leaveBalanceWarningText =
            document.getElementById('leave-balance-warning-text');


        let balanceCheckTimer = null;


        /*
        |--------------------------------------------------------------------------
        | Attachment Field
        |--------------------------------------------------------------------------
        */

        function updateAttachmentField() {

            if (
                !leaveType ||
                !attachmentWrapper ||
                !attachment ||
                !attachmentRequired ||
                !attachmentHelp
            ) {
                return;
            }


            const option =
                leaveType.options[leaveType.selectedIndex];


            if (!option || !option.value) {

                attachmentWrapper.classList.add('hidden');

                attachmentRequired.classList.add('hidden');

                attachment.removeAttribute('required');

                return;
            }


            attachmentWrapper.classList.remove('hidden');


            const required =
                option.dataset.fileRequired === '1';


            if (required) {

                attachmentRequired.classList.remove('hidden');

                attachment.setAttribute(
                    'required',
                    'required'
                );

                attachmentHelp.textContent =
                    'A supporting document is required for this leave type.';

            } else {

                attachmentRequired.classList.add('hidden');

                attachment.removeAttribute('required');

                attachmentHelp.textContent =
                    'You may upload a supporting document if required.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Show / Hide Leave Period
        |--------------------------------------------------------------------------
        */

        function toggleLeavePeriod() {

            if (
                !type ||
                !leavePeriodWrapper ||
                !leavePeriod
            ) {
                return;
            }


            if (type.value === 'half_day') {

                leavePeriodWrapper.classList.remove('hidden');

                leavePeriod.setAttribute(
                    'required',
                    'required'
                );

            } else {

                leavePeriodWrapper.classList.add('hidden');

                leavePeriod.removeAttribute(
                    'required'
                );

                leavePeriod.value = '';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Calculate Duration
        |--------------------------------------------------------------------------
        |
        | Full Day:
        |
        | 04 Sep -> 09 Sep
        | = 6 days
        | = 6.00
        |
        | Half Day:
        |
        | 04 Sep -> 09 Sep
        | = 6 calendar days
        | = 3.00
        |
        */

        function calculateDuration() {

            if (
                !type ||
                !fromDate ||
                !toDate ||
                !duration
            ) {
                return;
            }


            const selectedType =
                type.value;

            const fromValue =
                fromDate.value;

            const toValue =
                toDate.value;


            if (
                !selectedType ||
                !fromValue ||
                !toValue
            ) {

                duration.value = '0.00';

                return;

            }


            const startDate =
                new Date(
                    fromValue + 'T00:00:00'
                );

            const endDate =
                new Date(
                    toValue + 'T00:00:00'
                );


            /*
            |--------------------------------------------------------------------------
            | Validate Date Range
            |--------------------------------------------------------------------------
            */

            if (endDate < startDate) {

                duration.value = '0.00';

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Inclusive Calendar Days
            |--------------------------------------------------------------------------
            */

            const days =
                Math.floor(
                    (
                        endDate - startDate
                    ) /
                    (
                        1000 * 60 * 60 * 24
                    )
                ) + 1;


            /*
            |--------------------------------------------------------------------------
            | Full Day
            |--------------------------------------------------------------------------
            */

            if (
                selectedType === 'full_day'
            ) {

                duration.value =
                    days.toFixed(2);

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Half Day
            |--------------------------------------------------------------------------
            */

            if (
                selectedType === 'half_day'
            ) {

                duration.value =
                    (
                        days * 0.5
                    ).toFixed(2);

                return;

            }


            duration.value = '0.00';

        }


        /*
        |--------------------------------------------------------------------------
        | Reset Leave Balance Information
        |--------------------------------------------------------------------------
        */

        function resetLeaveBalanceInfo() {

            if (leaveBalanceInfo) {
                leaveBalanceInfo.classList.add('hidden');
            }

            if (leaveBalanceSuccess) {
                leaveBalanceSuccess.classList.add('hidden');
            }

            if (leaveBalanceWarning) {
                leaveBalanceWarning.classList.add('hidden');
            }

            if (leaveBalanceSuccessText) {
                leaveBalanceSuccessText.textContent = '';
            }

            if (leaveBalanceWarningText) {
                leaveBalanceWarningText.textContent = '';
            }

        }


        /*
        |--------------------------------------------------------------------------
        | Check Leave Balance
        |--------------------------------------------------------------------------
        */

        function checkLeaveBalance() {

            if (
                !type ||
                !leaveType ||
                !fromDate ||
                !toDate
            ) {
                return;
            }


            const typeValue =
                type.value;

            const leaveTypeValue =
                leaveType.value;

            const fromValue =
                fromDate.value;

            const toValue =
                toDate.value;


            /*
            |--------------------------------------------------------------------------
            | Required Values
            |--------------------------------------------------------------------------
            */

            if (
                !typeValue ||
                !leaveTypeValue ||
                !fromValue ||
                !toValue
            ) {

                resetLeaveBalanceInfo();

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Invalid Date Range
            |--------------------------------------------------------------------------
            */

            if (toValue < fromValue) {

                resetLeaveBalanceInfo();

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Debounce AJAX Request
            |--------------------------------------------------------------------------
            */

            clearTimeout(
                balanceCheckTimer
            );


            balanceCheckTimer =
                setTimeout(function() {

                    const params =
                        new URLSearchParams({

                            type:
                                typeValue,

                            leave_type_id:
                                leaveTypeValue,

                            requested_from_date:
                                fromValue,

                            requested_to_date:
                                toValue,

                        });


                    fetch(
                        `{{ route('leave-requests.check-balance') }}?${params.toString()}`,
                        {
                            headers: {
                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',
                            }
                        }
                    )
                    .then(function(response) {

                        if (!response.ok) {

                            throw new Error(
                                'Unable to check leave balance.'
                            );

                        }

                        return response.json();

                    })
                    .then(function(data) {

                        if (!leaveBalanceInfo) {
                            return;
                        }


                        leaveBalanceInfo.classList.remove(
                            'hidden'
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | No Balance Record
                        |--------------------------------------------------------------------------
                        */

                        if (!data.has_balance_record) {

                            if (leaveBalanceSuccess) {

                                leaveBalanceSuccess.classList.add(
                                    'hidden'
                                );

                            }

                            if (leaveBalanceWarning) {

                                leaveBalanceWarning.classList.remove(
                                    'hidden'
                                );

                            }

                            if (leaveBalanceWarningText) {

                                leaveBalanceWarningText.textContent =
                                    data.message;

                            }

                            return;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Balance Available
                        |--------------------------------------------------------------------------
                        */

                        if (data.eligible) {

                            if (leaveBalanceWarning) {

                                leaveBalanceWarning.classList.add(
                                    'hidden'
                                );

                            }

                            if (leaveBalanceSuccess) {

                                leaveBalanceSuccess.classList.remove(
                                    'hidden'
                                );

                            }

                            if (leaveBalanceSuccessText) {

                                leaveBalanceSuccessText.textContent =
                                    `${data.leave_type_name}: ` +
                                    `${Number(data.current_balance).toFixed(2)} ` +
                                    `days available. ` +
                                    `Request duration: ` +
                                    `${Number(data.duration).toFixed(2)} days.`;

                            }

                        } else {

                            if (leaveBalanceSuccess) {

                                leaveBalanceSuccess.classList.add(
                                    'hidden'
                                );

                            }

                            if (leaveBalanceWarning) {

                                leaveBalanceWarning.classList.remove(
                                    'hidden'
                                );

                            }

                            if (leaveBalanceWarningText) {

                                leaveBalanceWarningText.textContent =
                                    data.message;

                            }

                        }

                    })
                    .catch(function() {

                        resetLeaveBalanceInfo();

                    });

                }, 300);

        }


        /*
        |--------------------------------------------------------------------------
        | Event Listeners
        |--------------------------------------------------------------------------
        */

        if (leaveType) {

            leaveType.addEventListener(
                'change',
                updateAttachmentField
            );

            leaveType.addEventListener(
                'change',
                checkLeaveBalance
            );

        }


        if (type) {

            type.addEventListener(
                'change',
                toggleLeavePeriod
            );

            type.addEventListener(
                'change',
                calculateDuration
            );

            type.addEventListener(
                'change',
                checkLeaveBalance
            );

        }


        if (fromDate) {

            fromDate.addEventListener(
                'change',
                calculateDuration
            );

            fromDate.addEventListener(
                'change',
                checkLeaveBalance
            );

        }


        if (toDate) {

            toDate.addEventListener(
                'change',
                calculateDuration
            );

            toDate.addEventListener(
                'change',
                checkLeaveBalance
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Initial State
        |--------------------------------------------------------------------------
        */

        updateAttachmentField();

        toggleLeavePeriod();

        calculateDuration();

        checkLeaveBalance();

    });
</script>


@endpush

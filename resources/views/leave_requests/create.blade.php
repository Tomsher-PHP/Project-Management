@extends('layouts.master')

@section('page-content')

    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl bg-white shadow-sm dark:bg-darkblack-600">

            <form
                action="{{ route('leave-requests.store') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf
                <input
                type="hidden"
                name="user_id"
                value="{{ auth()->id() }}">

                <div class="p-6">

                    <div class="grid grid-cols-4 gap-2 md:grid-cols-4">



                        {{-- Leave Type --}}
                        <div>

                            <label
                                for="leave_type_id"
                                class="mb-2 block text-sm font-medium">

                                Leave Type
                                <span class="text-red-500">*</span>

                            </label>

                            <select
                                name="leave_type_id"
                                id="leave_type_id"
                                required
                                class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                <option value="">
                                    Select Leave Type
                                </option>

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
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        {{-- Type --}}
                        <div>

                            <label
                                for="type"
                                class="mb-2 block text-sm font-medium">

                                Type
                                <span class="text-red-500">*</span>

                            </label>

                            <select
                                name="type"
                                id="type"
                                required
                                class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                <option value="">
                                    Select Type
                                </option>

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
                                <p class="mt-1 text-xs text-red-500">
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
                                class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">

                                Leave Period
                                <span class="text-red-500">*</span>

                            </label>

                            <select
                                name="half_day_type"
                                id="half_day_type"
                                class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

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
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        {{-- Eligibility message --}}
                        <div
                            id="eligibility-message"
                            class="hidden md:col-span-2 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">

                            <strong>
                                Leave Balance Notice:
                            </strong>

                            <span id="eligibility-message-text">
                                You may not have sufficient leave balance for this request.
                            </span>

                            <br>

                            <span class="text-xs">
                                You can still submit the request.
                                The final leave balance will be checked when the request is approved.
                            </span>

                        </div>


                        {{-- From Date --}}
                        <div>

                            <label
                                for="requested_from_date"
                                class="mb-2 block text-sm font-medium">

                                From Date
                                <span class="text-red-500">*</span>

                            </label>

                            <input
                                type="date"
                                name="requested_from_date"
                                id="requested_from_date"
                                value="{{ old('requested_from_date') }}"
                                required
                                class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                            @error('requested_from_date')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        {{-- To Date --}}
                        <div>

                            <label
                                for="requested_to_date"
                                class="mb-2 block text-sm font-medium">

                                To Date
                                <span class="text-red-500">*</span>

                            </label>

                            <input
                                type="date"
                                name="requested_to_date"
                                id="requested_to_date"
                                value="{{ old('requested_to_date') }}"
                                required
                                class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                            @error('requested_to_date')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        {{-- Duration --}}
                        <div>

                            <label
                                class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">

                                Duration

                            </label>

                            <input
                                type="text"
                                id="duration_display"
                                value="0.00"
                                readonly
                                class="w-full rounded-lg border border-bgray-200 bg-bgray-50 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                            <p class="mt-1 text-xs text-bgray-500">
                                Automatically calculated.
                            </p>

                        </div>


                        {{-- Leave Balance Information --}}
                        <div
                            id="leave-balance-info"
                            class="hidden md:col-span-2">

                            {{-- Success --}}
                            <div
                                id="leave-balance-success"
                                class="hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">

                                <strong>
                                    Leave Balance:
                                </strong>

                                <span id="leave-balance-success-text"></span>

                            </div>


                            {{-- Warning --}}
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
                                class="mb-2 block text-sm font-medium">

                                Reason
                                <span class="text-red-500">*</span>

                            </label>

                            <textarea
                                name="reason"
                                id="reason"
                                rows="5"
                                required
                                placeholder="Enter the reason for your leave..."
                                class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">{{ old('reason') }}</textarea>

                            @error('reason')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        {{-- Attachment --}}
                        <div
                            id="attachment-wrapper"
                            class="hidden md:col-span-2">

                            <label
                                for="attachment"
                                class="mb-2 block text-sm font-medium">

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
                                class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                            <p
                                id="attachment-help"
                                class="mt-1 text-xs text-bgray-500">

                                You may upload a supporting document if required.

                            </p>

                            @error('attachment')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- Buttons --}}
                <div
                    class="flex justify-end gap-3 border-t border-bgray-100 px-6 py-4">

                    <a
                        href="{{ route('leave-requests.index') }}"
                        class="rounded-lg border border-bgray-200 px-5 py-2.5 text-sm">

                        Cancel

                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-medium text-white">

                        Submit Leave Request

                    </button>

                </div>

            </form>

        </div>

    </div>


<script>
document.addEventListener('DOMContentLoaded', function () {

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

            /*
             * Half Day selected.
             *
             * First Half / Second Half is required.
             */
            leavePeriodWrapper.classList.remove('hidden');

            leavePeriod.setAttribute(
                'required',
                'required'
            );

        } else {

            /*
             * Full Day selected.
             *
             * Leave Period is not required.
             */
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
    | = 6 × 0.5
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
         * From date cannot be after To date.
         */
        if (endDate < startDate) {

            duration.value = '0.00';

            return;

        }


        /*
         * Inclusive number of calendar days.
         *
         * 04 -> 09
         *
         * 04
         * 05
         * 06
         * 07
         * 08
         * 09
         *
         * = 6 days
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
         * Full Day
         */
        if (
            selectedType === 'full_day'
        ) {

            duration.value =
                days.toFixed(2);

            return;

        }


        /*
         * Half Day
         *
         * Every date in the range
         * counts as 0.5 day.
         *
         * 04 -> 04 = 0.50
         *
         * 04 -> 05 = 1.00
         *
         * 04 -> 09 = 3.00
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
    | Reset Balance Information
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
         * Do NOT restrict half-day
         * to a single date.
         *
         * Half-day can be:
         *
         * 04 -> 09
         * First Half
         *
         * or
         *
         * 04 -> 09
         * Second Half
         */


        if (toValue < fromValue) {

            resetLeaveBalanceInfo();

            return;

        }


        clearTimeout(
            balanceCheckTimer
        );


        balanceCheckTimer =
            setTimeout(function () {

                const params =
                    new URLSearchParams({

                        type: typeValue,

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
                .then(function (response) {

                    if (!response.ok) {

                        throw new Error(
                            'Unable to check leave balance.'
                        );

                    }

                    return response.json();

                })
                .then(function (data) {

                    if (!leaveBalanceInfo) {
                        return;
                    }


                    leaveBalanceInfo.classList.remove(
                        'hidden'
                    );


                    /*
                     * No balance record.
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
                     * Balance available.
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
                .catch(function () {

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

@endsection

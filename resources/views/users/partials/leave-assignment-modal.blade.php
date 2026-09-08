@php
    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';

    $balances = $balances ?? collect();

    $firstBalance = $balances->first();

    $formAction = $isEdit
        ? route('users.leave-details.update', [
            'user' => $user->id,
            'year' => $editYear,
        ])
        : route('users.leave-assignment.store', $user->id);

    $defaultFrom =
        $isEdit && $firstBalance
            ? $firstBalance->valid_from?->format('Y-m-d')
            : ($defaultValidFrom ?? now()->startOfYear())->format('Y-m-d');

    $defaultTo =
        $isEdit && $firstBalance
            ? $firstBalance->valid_to?->format('Y-m-d')
            : ($defaultValidTo ?? now()->endOfYear())->format('Y-m-d');
@endphp

@php
    $hasCarryForward = $isEdit
        ? $balances->contains(function ($balance) {
            return (bool) $balance->is_carry_forward;
        })
        : false;
@endphp


<div class="modal fixed inset-0 z-50 overflow-y-auto modal-form block" id="leaveAssignmentModal"
    style="z-index: 1050 !important;">

    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">

        <div class="modal-content relative z-10 w-full max-w-5xl">

            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">

                {{-- Header --}}
                <div
                    class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">

                    <div>

                        <h3 class="text-2xl font-semibold text-bgray-900 dark:text-white">

                            {{ $isEdit ? 'Edit Leave Assignment' : 'Leave Assignment' }}

                        </h3>

                        <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">

                            {{ $isEdit ? 'Update leave details for' : 'Assign leave allowances for' }}

                            <span class="font-bold text-success-400">
                                {{ $user->name }}
                            </span>

                        </p>

                    </div>


                    <a href="{{ $isEdit
                        ? route('users.leave-details', $user->id)
                        : ($returnTo === 'leave_details'
                            ? route('users.leave-details', $user->id)
                            : route('users.index')) }}"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300"
                        title="Close">
                        ✕
                    </a>

                </div>


                {{-- Form --}}
                <form id="leave-assignment-form" action="{{ $formAction }}" method="POST">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    @if (!$isEdit)
                        <input type="hidden"
                            name="return_to"
                            value="{{ $returnTo ?? 'users.index' }}">
                    @endif

                    <div class="max-h-[70vh] overflow-y-auto px-6 py-6 sm:px-7">
                        {{-- Top Information --}}
                        @if ($isEdit)
                            <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300">
                                <div class="font-semibold">
                                    Editing {{ $editYear }} Leave Assignment
                                </div>
                                <p class="mt-1 text-xs">
                                    You can update the entitlement values and carry-forward amount.
                                    To create a new year's leave assignment, close this form and use
                                    <strong>Add New Leave Year</strong>.
                                </p>
                            </div>
                        @else
                            <div class="mb-5 rounded-lg border border-blue-200  px-4 py-3 text-sm text-blue-700 dark:border-blue-900/40 dark:text-blue-300">
                                Define the entitlement period, yearly entitlement, monthly entitlement
                                and opening balance for each leave type.
                            </div>
                        @endif

                        {{-- Session Error --}}
                        @if (session('error'))
                            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                                {{ session('error') }}
                            </div>

                        @endif


                        {{-- Validation Errors --}}
                        @if ($errors->any())

                            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">

                                <ul class="list-inside list-disc">

                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach

                                </ul>

                            </div>

                        @endif


                        {{-- Year Change Warning --}}
                        <div id="leave-year-change-warning"
                            class="mb-5 hidden rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-4 text-sm text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300">

                            <div class="flex items-start gap-3">

                                <svg class="mt-0.5 h-5 w-5 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01M10.29 3.86l-7.1 12.28A2 2 0 004.93 19h14.14a2 2 0 001.74-2.86l-7.1-12.28a2 2 0 00-3.42 0z"
                                    />

                                </svg>


                                <div>

                                    <div class="font-semibold">
                                        Entitlement year change detected.
                                    </div>

                                    <p class="mt-1">
                                        This leave assignment currently belongs to
                                        <strong>{{ $editYear ?? '' }}</strong>,
                                        but the selected From Date belongs to another year.
                                    </p>

                                    <p class="mt-2 font-medium">
                                        To create a new year's leave assignment, close this form and use
                                        <strong>Add New Leave Year</strong>.
                                    </p>

                                </div>

                            </div>

                        </div>


                        {{-- Leave Assignment Period --}}
                        <div class="mb-6">

                            <div class="mb-4">

                                <h4 class="text-base font-semibold text-bgray-900 dark:text-white">
                                    Leave Assignment Period
                                </h4>

                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                                    Define the period for which these leave entitlements apply.
                                </p>

                            </div>


                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                                {{-- From Date --}}
                                <div class="flex flex-col gap-2">

                                    <label for="leave_valid_from"
                                        class="text-sm font-medium text-bgray-600 dark:text-bgray-50">

                                        From Date
                                        <x-red-star />

                                    </label>

                                    <input
                                        type="date"
                                        name="valid_from"
                                        id="leave_valid_from"
                                        value="{{ old('valid_from', $defaultFrom) }}"
                                        class="w-full rounded-lg border border-gray-300 p-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                        required
                                    >

                                    @error('valid_from')

                                        <p class="mt-1 text-sm text-error-300">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>


                                {{-- To Date --}}
                                <div class="flex flex-col gap-2">

                                    <label for="leave_valid_to"
                                        class="text-sm font-medium text-bgray-600 dark:text-bgray-50">

                                        To Date
                                        <x-red-star />

                                    </label>

                                    <input
                                        type="date"
                                        name="valid_to"
                                        id="leave_valid_to"
                                        value="{{ old('valid_to', $defaultTo) }}"
                                        class="w-full rounded-lg border border-gray-300 p-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                        required
                                    >

                                    @error('valid_to')

                                        <p class="mt-1 text-sm text-error-300">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>

                            </div>

                        </div>


                        {{-- Carry Forward --}}
                        @php

                            /*
                            * In edit mode, determine whether any existing leave
                            * balance for this period has carry-forward enabled.
                            */
                            $hasCarryForward = $isEdit
                                ? $balances->contains(function ($balance) {
                                    return (bool) $balance->is_carry_forward;
                                })
                                : false;

                        @endphp


                        <div class="mb-6 rounded-lg border border-bgray-200 p-5 dark:border-darkblack-400">
                            <h4 class="text-base font-semibold text-bgray-900 dark:text-white">
                                Carry Forward Previous Year Balance
                            </h4>

                            @if ($previousBalances->isNotEmpty())
                                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                                    The employee has leave balance remaining from the previous
                                    entitlement period. Do you want to carry forward any of it?
                                </p>

                                <div class="mt-4 flex gap-6">
                                    {{-- No --}}
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            name="carry_forward"
                                            value="no"
                                            class="carry-forward-radio h-4 w-4"
                                            {{ $isEdit
                                                ? (!$hasCarryForward ? 'checked' : '')
                                                : (old('carry_forward', 'no') === 'no' ? 'checked' : '')
                                            }}>
                                        <span class="text-sm text-bgray-700 dark:text-white">
                                            No
                                        </span>

                                    </label>


                                    {{-- Yes --}}
                                    <label class="flex items-center gap-2">

                                        <input
                                            type="radio"
                                            name="carry_forward"
                                            value="yes"
                                            class="carry-forward-radio h-4 w-4"
                                            {{ $isEdit
                                                ? ($hasCarryForward ? 'checked' : '')
                                                : (old('carry_forward') === 'yes' ? 'checked' : '')
                                            }}
                                        >

                                        <span class="text-sm text-bgray-700 dark:text-white">
                                            Yes, Carry Forward
                                        </span>

                                    </label>

                                </div>


                                <p class="mt-2 text-xs text-bgray-500 dark:text-bgray-400">
                                    You can select a different carry-forward amount for each leave type below.
                                </p>

                            @else

                                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                                    No previous leave balance is available for carry forward.
                                </p>

                                <input
                                    type="hidden"
                                    name="carry_forward"
                                    value="no"
                                >

                            @endif

                        </div>


                        {{-- Leave Types --}}
                        <div class="overflow-x-auto">

                            <table class="w-full min-w-[1050px]">

                                <thead>

                                    <tr class="border-b border-bgray-200 dark:border-darkblack-400">

                                        <th class="px-4 py-3 text-left text-sm font-semibold text-bgray-700 dark:text-white">
                                            Leave Type
                                        </th>

                                        <th class="px-4 py-3 text-left text-sm font-semibold text-bgray-700 dark:text-white">
                                            Yearly Entitlement
                                        </th>

                                        <th class="px-4 py-3 text-left text-sm font-semibold text-bgray-700 dark:text-white">
                                            Monthly Entitlement
                                        </th>

                                        <th class="px-4 py-3 text-left text-sm font-semibold text-bgray-700 dark:text-white">
                                            Opening Balance
                                        </th>

                                        {{-- Previous Year / Carry Forward --}}
                                        <th
                                            id="carry-forward-column"
                                            class="px-4 py-3 text-left text-sm font-semibold text-bgray-700 dark:text-white {{ ($isEdit && $hasCarryForward) ? '' : 'hidden' }}"
                                        >
                                            Previous Year Balance
                                        </th>

                                        <th class="px-4 py-3 text-left text-sm font-semibold text-bgray-700 dark:text-white">
                                            Total Balance
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach ($leaveTypes as $leaveType)

                                        @php

                                            $balance = $balances->firstWhere(
                                                'leave_type_id',
                                                $leaveType->id
                                            );

                                            $previousBalance = $previousBalances->firstWhere(
                                                'leave_type_id',
                                                $leaveType->id
                                            );

                                            $previousAvailable = $previousBalance
                                                ? (float) $previousBalance->current_balance
                                                : 0;

                                            $existingCarryForward = $balance
                                                ? (float) $balance->carry_forward_balance
                                                : 0;

                                            $initialOpeningBalance = $balance
                                                ? (float) $balance->opening_balance
                                                : 0;

                                            $initialTotalBalance =
                                                $initialOpeningBalance +
                                                $existingCarryForward;

                                        @endphp


                                        <tr class="border-b border-bgray-100 dark:border-darkblack-400">

                                            {{-- Leave Type --}}
                                            <td class="px-4 py-4">

                                                <div class="font-medium text-bgray-900 dark:text-white">
                                                    {{ $leaveType->name }}
                                                </div>


                                                @if ($leaveType->is_paid)

                                                    <span class="text-xs text-green-600 dark:text-green-400">
                                                        Paid
                                                    </span>

                                                @else

                                                    <span class="text-xs text-red-500">
                                                        Unpaid
                                                    </span>

                                                @endif


                                                <input
                                                    type="hidden"
                                                    name="leave_balances[{{ $leaveType->id }}][leave_type_id]"
                                                    value="{{ $leaveType->id }}"
                                                >

                                            </td>


                                            {{-- Yearly Entitlement --}}
                                            <td class="px-4 py-4">

                                                <input
                                                    type="number"
                                                    name="leave_balances[{{ $leaveType->id }}][yearly_entitlement]"
                                                    value="{{ old(
                                                        "leave_balances.$leaveType->id.yearly_entitlement",
                                                        $balance?->yearly_entitlement ?? 0
                                                    ) }}"
                                                    min="0"
                                                    step="0.5"
                                                    class="leave-yearly-input w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                                    data-leave-type="{{ $leaveType->id }}"
                                                    required
                                                >

                                            </td>


                                            {{-- Monthly Entitlement --}}
                                            <td class="px-4 py-4">

                                                <input
                                                    type="number"
                                                    name="leave_balances[{{ $leaveType->id }}][monthly_entitlement]"
                                                    value="{{ old(
                                                        "leave_balances.$leaveType->id.monthly_entitlement",
                                                        $balance?->monthly_entitlement ?? 0
                                                    ) }}"
                                                    min="0"
                                                    step="0.5"
                                                    class="w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                                    required
                                                >

                                            </td>


                                            {{-- Opening Balance --}}
                                            <td class="px-4 py-4">

                                                <input
                                                    type="number"
                                                    name="leave_balances[{{ $leaveType->id }}][opening_balance]"
                                                    value="{{ old(
                                                        "leave_balances.$leaveType->id.opening_balance",
                                                        $balance?->opening_balance ?? 0
                                                    ) }}"
                                                    min="0"
                                                    step="0.5"
                                                    class="leave-opening-input w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                                    data-leave-type="{{ $leaveType->id }}"
                                                    required
                                                >

                                            </td>


                                            {{-- Previous Year Balance / Carry Forward --}}
                                            <td
                                                class="carry-forward-column px-4 py-4 {{ ($isEdit && $hasCarryForward) ? '' : 'hidden' }}"
                                                data-leave-type="{{ $leaveType->id }}"
                                            >

                                                @if ($isEdit)

                                                    @if ($previousAvailable > 0)

                                                        <div class="mb-1 text-xs text-bgray-500 dark:text-bgray-400">
                                                            Previous Available:
                                                            <strong>
                                                                {{ number_format($previousAvailable, 2) }}
                                                            </strong>
                                                        </div>

                                                        <input
                                                            type="number"
                                                            name="leave_balances[{{ $leaveType->id }}][carry_forward_balance]"
                                                            value="{{ old(
                                                                "leave_balances.$leaveType->id.carry_forward_balance",
                                                                $existingCarryForward
                                                            ) }}"
                                                            min="0"
                                                            max="{{ $previousAvailable }}"
                                                            step="0.5"
                                                            class="carry-forward-input w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white"
                                                            data-leave-type="{{ $leaveType->id }}"
                                                            data-max="{{ $previousAvailable }}"
                                                        >

                                                    @else

                                                        <input
                                                            type="number"
                                                            name="leave_balances[{{ $leaveType->id }}][carry_forward_balance]"
                                                            value="0"
                                                            min="0"
                                                            step="0.5"
                                                            class="carry-forward-input w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white"
                                                            data-leave-type="{{ $leaveType->id }}"
                                                            data-max="0"
                                                        >

                                                        <p class="mt-1 text-xs text-bgray-400">
                                                            No previous balance
                                                        </p>

                                                    @endif

                                                @else

                                                    @if ($previousAvailable > 0)

                                                        <div class="mb-1 text-xs text-bgray-500 dark:text-bgray-400">
                                                            Available:
                                                            <strong>
                                                                {{ number_format($previousAvailable, 2) }}
                                                            </strong>
                                                        </div>

                                                        <input
                                                            type="number"
                                                            name="carry_forward_items[{{ $leaveType->id }}][amount]"
                                                            value="{{ old(
                                                                "carry_forward_items.$leaveType->id.amount",
                                                                0
                                                            ) }}"
                                                            min="0"
                                                            max="{{ $previousAvailable }}"
                                                            step="0.5"
                                                            class="carry-forward-input w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white"
                                                            data-leave-type="{{ $leaveType->id }}"
                                                            data-max="{{ $previousAvailable }}"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="carry_forward_items[{{ $leaveType->id }}][selected]"
                                                            value="0"
                                                            class="carry-forward-selected"
                                                            data-leave-type="{{ $leaveType->id }}"
                                                        >

                                                    @else

                                                        <span class="text-xs text-bgray-400">
                                                            No balance
                                                        </span>

                                                    @endif

                                                @endif

                                            </td>


                                            {{-- Total Balance --}}
                                            <td class="px-4 py-4">

                                                <span
                                                    id="total-balance-{{ $leaveType->id }}"
                                                    class="font-semibold text-success-400"
                                                >
                                                    {{ number_format($initialTotalBalance, 2) }}
                                                </span>

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>


                    {{-- Footer --}}
                    <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">

                        <a
                            href="{{ $isEdit
                                ? route('users.leave-details', $user->id)
                                : ($returnTo === 'leave_details'
                                    ? route('users.leave-details', $user->id)
                                    : route('users.index'))
                            }}"
                            class="rounded-lg border border-bgray-300 bg-white px-4 py-2 text-sm text-bgray-700 transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            id="save-leave-assignment"
                            class="rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition hover:bg-success-400"
                        >
                            {{ $isEdit ? 'Update Leave Assignment' : 'Save Leave Assignment' }}
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        /*
        |--------------------------------------------------------------------------
        | Elements
        |--------------------------------------------------------------------------
        */

        const fromDate = document.getElementById('leave_valid_from');
        const toDate = document.getElementById('leave_valid_to');

        const warning = document.getElementById('leave-year-change-warning');
        const saveButton = document.getElementById('save-leave-assignment');

        const carryForwardRadios =
            document.querySelectorAll('.carry-forward-radio');

        const carryForwardColumn =
            document.getElementById('carry-forward-column');

        const carryForwardColumns =
            document.querySelectorAll('.carry-forward-column');

        const openingInputs =
            document.querySelectorAll('.leave-opening-input');

        const carryForwardInputs =
            document.querySelectorAll('.carry-forward-input');


        /*
        |--------------------------------------------------------------------------
        | Original year
        |--------------------------------------------------------------------------
        */

        @if ($isEdit)
            const originalYear = {{ (int) $editYear }};
        @else
            const originalYear = null;
        @endif


        /*
        |--------------------------------------------------------------------------
        | Check whether carry forward is enabled
        |--------------------------------------------------------------------------
        */

        function isCarryForwardEnabled() {

            const selected =
                document.querySelector(
                    '.carry-forward-radio:checked'
                );

            return selected &&
                selected.value === 'yes';
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate total balance
        |
        | Total Balance =
        | Opening Balance + Carry Forward
        |--------------------------------------------------------------------------
        */

        function calculateTotalBalance(leaveTypeId) {

            const openingInput =
                document.querySelector(
                    `.leave-opening-input[data-leave-type="${leaveTypeId}"]`
                );

            const carryForwardInput =
                document.querySelector(
                    `.carry-forward-input[data-leave-type="${leaveTypeId}"]`
                );

            const totalElement =
                document.getElementById(
                    `total-balance-${leaveTypeId}`
                );


            if (!openingInput || !totalElement) {
                return;
            }


            let openingBalance =
                parseFloat(openingInput.value) || 0;

            let carryForwardBalance = 0;


            /*
             * Carry forward is included only when
             * Yes is selected.
             */
            if (
                isCarryForwardEnabled() &&
                carryForwardInput
            ) {

                carryForwardBalance =
                    parseFloat(carryForwardInput.value) || 0;


                const maximum =
                    parseFloat(
                        carryForwardInput.dataset.max
                    ) || 0;


                /*
                 * Never allow carry forward to exceed
                 * the previous year's available balance.
                 */
                if (carryForwardBalance > maximum) {

                    carryForwardBalance = maximum;

                    carryForwardInput.value =
                        maximum.toFixed(2);
                }


                /*
                 * Don't allow negative values.
                 */
                if (carryForwardBalance < 0) {

                    carryForwardBalance = 0;

                    carryForwardInput.value = '0.00';
                }
            }


            /*
             * Don't allow negative opening balance.
             */
            if (openingBalance < 0) {

                openingBalance = 0;

                openingInput.value = '0.00';
            }


            const total =
                openingBalance + carryForwardBalance;


            totalElement.textContent =
                total.toFixed(2);
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate all leave type totals
        |--------------------------------------------------------------------------
        */

        function calculateAllTotals() {

            openingInputs.forEach(function (input) {

                calculateTotalBalance(
                    input.dataset.leaveType
                );

            });
        }


        /*
        |--------------------------------------------------------------------------
        | Show / hide carry-forward column
        |--------------------------------------------------------------------------
        */

        function updateCarryForwardColumns() {

            const enabled =
                isCarryForwardEnabled();


            if (!carryForwardColumn) {
                calculateAllTotals();
                return;
            }


            if (enabled) {

                /*
                 * Show header.
                 */
                carryForwardColumn.classList.remove('hidden');


                /*
                 * Show all row cells.
                 */
                carryForwardColumns.forEach(function (column) {

                    column.classList.remove('hidden');

                });

            } else {

                /*
                 * Hide header.
                 */
                carryForwardColumn.classList.add('hidden');


                /*
                 * Hide all row cells.
                 */
                carryForwardColumns.forEach(function (column) {

                    column.classList.add('hidden');

                });


                /*
                 * When No is selected:
                 * reset all carry-forward values to zero.
                 *
                 * This means:
                 *
                 * is_carry_forward = false
                 * carry_forward_balance = 0
                 */
                carryForwardInputs.forEach(function (input) {

                    input.value = '0';

                });


                /*
                 * Reset create-mode hidden selection values.
                 */
                document
                    .querySelectorAll('.carry-forward-selected')
                    .forEach(function (selected) {

                        selected.value = '0';

                    });

            }


            calculateAllTotals();
        }


        /*
        |--------------------------------------------------------------------------
        | Validate entitlement year during Edit
        |
        | Example:
        |
        | Existing year = 2027
        | Admin changes From Date = 2028
        |
        | Don't allow save.
        |--------------------------------------------------------------------------
        */

        function checkYearChange() {

            /*
             * Only applicable in Edit mode.
             */
            if (
                !originalYear ||
                !fromDate ||
                !fromDate.value
            ) {

                if (warning) {
                    warning.classList.add('hidden');
                }

                if (saveButton) {

                    saveButton.disabled = false;

                    saveButton.classList.remove(
                        'cursor-not-allowed',
                        'opacity-50'
                    );
                }

                return;
            }


            const selectedYear =
                parseInt(
                    fromDate.value.substring(0, 4),
                    10
                );


            if (selectedYear !== originalYear) {

                /*
                 * Show warning.
                 */
                if (warning) {
                    warning.classList.remove('hidden');
                }


                /*
                 * Disable update.
                 */
                if (saveButton) {

                    saveButton.disabled = true;

                    saveButton.classList.add(
                        'cursor-not-allowed',
                        'opacity-50'
                    );
                }

            } else {

                /*
                 * Hide warning.
                 */
                if (warning) {
                    warning.classList.add('hidden');
                }


                /*
                 * Enable update.
                 */
                if (saveButton) {

                    saveButton.disabled = false;

                    saveButton.classList.remove(
                        'cursor-not-allowed',
                        'opacity-50'
                    );
                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Validate From / To dates
        |
        | To Date cannot be earlier than From Date.
        |--------------------------------------------------------------------------
        */

        function validateDateRange() {

            if (!fromDate || !toDate) {
                return;
            }


            if (
                fromDate.value &&
                toDate.value &&
                toDate.value < fromDate.value
            ) {

                toDate.setCustomValidity(
                    'To Date must be greater than or equal to From Date.'
                );

            } else {

                toDate.setCustomValidity('');
            }

        }


        /*
        |--------------------------------------------------------------------------
        | Opening balance events
        |--------------------------------------------------------------------------
        */

        openingInputs.forEach(function (input) {

            input.addEventListener('input', function () {

                calculateTotalBalance(
                    input.dataset.leaveType
                );

            });

        });


        /*
        |--------------------------------------------------------------------------
        | Carry-forward amount events
        |--------------------------------------------------------------------------
        */

        carryForwardInputs.forEach(function (input) {

            input.addEventListener('input', function () {

                let value =
                    parseFloat(input.value) || 0;

                const maximum =
                    parseFloat(
                        input.dataset.max
                    ) || 0;


                /*
                 * Prevent negative values.
                 */
                if (value < 0) {

                    value = 0;

                    input.value = '0.00';
                }


                /*
                 * Prevent exceeding previous balance.
                 */
                if (value > maximum) {

                    value = maximum;

                    input.value =
                        maximum.toFixed(2);

                }


                /*
                 * Create mode uses the hidden selected field.
                 *
                 * Amount > 0 = selected
                 * Amount = 0 = not selected
                 */
                const selected =
                    document.querySelector(
                        `.carry-forward-selected[data-leave-type="${input.dataset.leaveType}"]`
                    );


                if (selected) {

                    selected.value =
                        value > 0 ? '1' : '0';

                }


                calculateTotalBalance(
                    input.dataset.leaveType
                );

            });

        });


        /*
        |--------------------------------------------------------------------------
        | Carry-forward Yes / No events
        |--------------------------------------------------------------------------
        */

        carryForwardRadios.forEach(function (radio) {

            radio.addEventListener(
                'change',
                updateCarryForwardColumns
            );

        });


        /*
        |--------------------------------------------------------------------------
        | Date events
        |--------------------------------------------------------------------------
        */

        if (fromDate) {

            fromDate.addEventListener(
                'change',
                function () {

                    checkYearChange();
                    validateDateRange();

                }
            );

        }


        if (toDate) {

            toDate.addEventListener(
                'change',
                function () {

                    validateDateRange();

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Initial state
        |--------------------------------------------------------------------------
        */

        updateCarryForwardColumns();

        calculateAllTotals();

        checkYearChange();

        validateDateRange();

    });
</script>

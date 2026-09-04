@extends('layouts.master')
@section('page-content')
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-bgray-900 dark:text-white">
                    Leave Details
                </h2>
                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                    Leave entitlement and balance details for
                    <span class="font-semibold">
                        {{ $user->name }}
                    </span>
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('users.index') }}"
                    class="inline-flex items-center rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    Back
                </a>
                @can('user.leave_details.create')
                    <a href="{{ route('users.leave-details', ['user' => $user->id, 'add' => 1]) }}"
                        class="inline-flex items-center rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white hover:bg-success-400">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New Leave Year
                    </a>
                @endcan
            </div>
        </div>

        {{-- Messages --}}
        @if (session('success'))
            <div
                class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div
                class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        {{-- Year change warning --}}
        @if (session('year_change_warning'))
            <div
                class="mb-5 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-4 text-sm text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86l-7.1 12.28A2 2 0 004.93 19h14.14a2 2 0 001.74-2.86l-7.1-12.28a2 2 0 00-3.42 0z" />
                    </svg>
                    <div>
                        {{ session('year_change_warning') }}
                        <div class="mt-2 font-medium">
                            Please close the edit form and use
                            <strong>Add New Leave Year</strong>
                            to create a new entitlement period.
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Employee Information --}}
        <div class="mb-6 rounded-xl bg-white p-5 shadow-sm dark:bg-darkblack-600">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs uppercase text-bgray-500">
                        Employee
                    </p>
                    <p class="mt-1 font-semibold text-bgray-900 dark:text-white">
                        {{ $user->name }}
                    </p>
                </div>

                <div>
                    <p class="text-xs uppercase text-bgray-500">
                        Email
                    </p>
                    <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                        {{ $user->email ?? '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase text-bgray-500">
                        Leave Periods
                    </p>
                    <p class="mt-1 font-semibold text-bgray-900 dark:text-white">
                        {{ $leavePeriods->count() }}
                    </p>
                </div>

                <div>
                    <p class="text-xs uppercase text-bgray-500">
                        Leave Types Assigned
                    </p>
                    <p class="mt-1 font-semibold text-bgray-900 dark:text-white">
                        {{ $leaveBalances->pluck('leave_type_id')->unique()->count() }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Leave Periods --}}
        @forelse($leavePeriods as $year => $periodBalances)
            @php
                $periodFrom = $periodBalances->min('valid_from');
                $periodTo = $periodBalances->max('valid_to');
            @endphp
            <div class="mb-6 overflow-hidden rounded-xl bg-white shadow-sm dark:bg-darkblack-600">
                {{-- Period Header --}}
                <div
                    class="flex flex-col gap-4 border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">
                            {{ $year }} Leave Year
                        </h3>
                        <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                            {{ $periodFrom?->format('d M Y') }}
                            -
                            {{ $periodTo?->format('d M Y') }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        @can('user.leave_details.edit')
                            <a href="{{ route('users.leave-details', [
                                'user' => $user->id,
                                'edit_year' => $year,
                            ]) }}"
                                class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-100 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-8.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 7.5-7.5z" />
                                </svg>
                                Edit
                            </a>
                        @endcan

                        @can('user.leave_details.delete')
                            <form
                                action="{{ route('users.leave-details.destroy', [
                                    'user' => $user->id,
                                    'year' => $year,
                                ]) }}"
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to delete the {{ $year }} leave assignment? This will remove all leave balances for this period.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 7h12M9 7V4h6v3m-8 0l1 13h8l1-13M10 11v6M14 11v6" />
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>

                {{-- Leave Balance Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[950px]">
                        <thead>
                            <tr class="border-b border-bgray-200 dark:border-darkblack-400">
                                <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                                    Leave Type
                                </th>
                                <th class="px-5 py-4 text-right text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                                    Yearly
                                </th>
                                <th class="px-5 py-4 text-right text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                                    Monthly
                                </th>
                                <th class="px-5 py-4 text-right text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                                    Opening
                                </th>
                                <th class="px-5 py-4 text-right text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                                    Used
                                </th>
                                <th class="px-5 py-4 text-right text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                                    Remaining
                                </th>
                                <th class="px-5 py-4 text-right text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                                    Paid Used
                                </th>
                                <th class="px-5 py-4 text-right text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                                    Unpaid Used
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($periodBalances as $balance)
                                <tr class="border-b border-bgray-100 dark:border-darkblack-400">
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-bgray-900 dark:text-white">
                                            {{ $balance->leaveType->name ?? '-' }}
                                        </div>
                                        @if ($balance->leaveType)
                                            @if ($balance->leaveType->is_paid)
                                                <span class="text-xs text-green-600 dark:text-green-400">
                                                    Paid
                                                </span>
                                            @else
                                                <span class="text-xs text-red-500">
                                                    Unpaid
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm">
                                        {{ number_format($balance->yearly_entitlement, 2) }}
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm">
                                        {{ number_format($balance->monthly_entitlement, 2) }}
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm">
                                        {{ number_format($balance->opening_balance, 2) }}
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm">
                                        {{ number_format($balance->used_balance, 2) }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-semibold text-success-400">
                                            {{ number_format($balance->current_balance, 2) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm text-green-600">
                                        {{ number_format($balance->paid_days_used, 2) }}
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm text-red-500">
                                        {{ number_format($balance->unpaid_days_used, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @empty
            <div class="rounded-xl bg-white px-6 py-12 text-center shadow-sm dark:bg-darkblack-600">
                <p class="text-sm text-bgray-500 dark:text-bgray-300">
                    No leave assignments found for this employee.
                </p>
                @can('user.leave_details.create')
                    <a href="{{ route('users.leave-details', [
                        'user' => $user->id,
                        'add' => 1,
                    ]) }}"
                        class="mt-4 inline-flex rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white">
                        Add New Leave Year
                    </a>
                @endcan
            </div>
        @endforelse

        {{-- Create Modal --}}
        @if ($showLeaveAssignmentModal && !$editYear)
            @include('users.partials.leave-assignment-modal', [
                'user' => $user,
                'leaveTypes' => $leaveTypes,
                'balances' => collect(),
                'mode' => 'create',
                'returnTo' => 'leave_details',
            ])
        @endif

        {{-- Edit Modal --}}
        @if ($editYear)
            @include('users.partials.leave-assignment-modal', [
                'user' => $user,
                'leaveTypes' => $leaveTypes,
                'balances' => $editBalances,
                'mode' => 'edit',
                'editYear' => $editYear,
                'returnTo' => 'leave_details',
            ])
        @endif
    </div>
@endsection

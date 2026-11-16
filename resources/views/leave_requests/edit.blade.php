
@extends('layouts.master')

@section('page-content')
    @php
        $approvalMode = $approvalMode ?? request()->boolean('approved_mode');
        $action = $action ?? request()->get('action');

        $isApproveAction = $action === 'approve';
        $isRejectAction = $action === 'reject';

        $fullEdit = $fullEdit ?? false;
        $restrictedEdit = $restrictedEdit ?? false;
        $dateFormat = $globalDateFormat ?? config('constants.date_format', 'd M Y');
    @endphp

    <div>
        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                <div class="font-semibold text-red-800">
                    Please correct the following errors:
                </div>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Success Message --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error Message --}}
        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <form
            id="leave-request-form"
            data-unpaid-leave-confirm
            method="POST"
            action="{{ route('leave-requests.update', $leaveRequest->id) }}"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')

            @if ($approvalMode)
                <input type="hidden" name="approved_mode" value="1">
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Main Form --}}
                <div class="lg:col-span-2">
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-darkblack-600 dark:ring-darkblack-400">

                        @include('leave_requests._form', [
                            'isCreate' => false,
                            'approvalMode' => $approvalMode,
                            'fullEdit' => $fullEdit,
                            'restrictedEdit' => $restrictedEdit,
                            'leaveRequest' => $leaveRequest,
                        ])

                        {{-- Form Buttons --}}
                        <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-darkblack-400">

                            {{-- Cancel --}}
                            <a
                                href="{{ route('leave-requests.index') }}"
                                class="rounded-lg border border-bgray-200 px-5 py-2.5 text-sm font-medium text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
                                Cancel
                            </a>

                            {{-- Normal Mode --}}
                            @if (!$approvalMode)
                                @can('leave_request.edit')
                                    <button
                                        type="submit"
                                        name="action"
                                        value="update"
                                        class="rounded-lg bg-success-400 px-5 py-2.5 text-sm font-semibold text-white transition">
                                        Update
                                    </button>
                                @endcan
                            @else
                                {{-- Approval Mode: Normal Update --}}
                                @can('leave_request.edit')
                                    <button
                                        type="submit"
                                        name="action"
                                        value="update"
                                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                        Update
                                    </button>
                                @endcan

                                {{-- Update & Approve --}}
                                @if ($isApproveAction)
                                    @can('leave_request.approve')
                                        <button
                                            type="submit"
                                            name="action"
                                            value="update_and_approve"
                                            class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                                            Update &amp; Approve
                                        </button>
                                    @endcan
                                @endif

                                {{-- Reject --}}
                                @if ($isRejectAction)
                                    @can('leave_request.reject')
                                        <button
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            class="rounded-lg bg-error-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-error-600">
                                            Reject
                                        </button>
                                    @endcan
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Right Side --}}
                <div class="space-y-6">

                    {{-- Request Information --}}
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-darkblack-600 dark:ring-darkblack-400">
                        <h2 class="mb-5 text-lg font-semibold text-bgray-900 dark:text-white">
                            Request Information
                        </h2>

                        <div class="space-y-4">

                            {{-- Employee --}}
                            <div>
                                <span class="block text-xs font-medium uppercase text-gray-500">
                                    Employee
                                </span>
                                <span class="mt-1 block text-sm font-medium text-gray-700 dark:text-bgray-50">
                                    {{ $leaveRequest->user->name ?? '-' }}
                                </span>
                            </div>

                            {{-- Status --}}
                            <div>
                                <span class="block text-xs font-medium uppercase text-gray-500">
                                    Status
                                </span>

                                <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    @if ($leaveRequest->status === 'approved')
                                        bg-green-100 text-green-800
                                    @elseif ($leaveRequest->status === 'rejected')
                                        bg-red-100 text-red-800
                                    @elseif ($leaveRequest->status === 'cancelled')
                                        bg-gray-100 text-gray-800
                                    @else
                                        bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($leaveRequest->status) }}
                                </span>
                            </div>

                            {{-- Submitted Date --}}
                            <div>
                                <span class="block text-xs font-medium uppercase text-gray-500">
                                    Submitted Date
                                </span>

                                @php
                                    $submittedDate = $leaveRequest->submitted_at ?? $leaveRequest->created_at;
                                @endphp

                                <span class="mt-1 block text-sm font-medium text-gray-700 dark:text-bgray-50">
                                    @if ($submittedDate)
                                        @appDateTime($submittedDate)
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>

                            {{-- Paid / Unpaid --}}
                            @if ($leaveRequest->status === 'approved' && ($leaveRequest->paid_days !== null || $leaveRequest->unpaid_days !== null))
                                <div>
                                    <span class="block text-xs font-medium uppercase text-gray-500">
                                        Paid Days
                                    </span>
                                    <span class="mt-1 block text-sm font-semibold text-green-700">
                                        {{ number_format((float) ($leaveRequest->paid_days ?? 0), 2) }}
                                    </span>
                                </div>

                                <div>
                                    <span class="block text-xs font-medium uppercase text-gray-500">
                                        Unpaid Days
                                    </span>
                                    <span class="mt-1 block text-sm font-semibold text-red-700">
                                        {{ number_format((float) ($leaveRequest->unpaid_days ?? 0), 2) }}
                                    </span>
                                </div>
                            @endif

                        </div>
                    </div>

                    {{-- Leave Balance --}}
                    <div id="leave-balance-info" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-darkblack-600 dark:ring-darkblack-400">
                        <h2 class="mb-5 text-lg font-semibold text-bgray-900 dark:text-white">
                            Leave Balance
                        </h2>

                        <div id="leave-balance-content">
                            <p class="text-sm text-gray-500">
                                Leave balance information will be displayed here.
                            </p>
                        </div>
                    </div>

                    {{-- Leave History --}}
                    @if (isset($leaveRequest->histories) && $leaveRequest->histories->count())
                        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-darkblack-600 dark:ring-darkblack-400">
                            <h2 class="mb-5 text-lg font-semibold text-bgray-900 dark:text-white">
                                Leave History
                            </h2>

                            <div class="space-y-4">
                                @foreach ($leaveRequest->histories as $history)
                                    <div class="border-l-2 border-gray-200 pl-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-medium text-gray-700 dark:text-bgray-50">
                                                    {{ ucwords(str_replace('_', ' ', $history->action)) }}
                                                </p>

                                                @if ($history->user)
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        By {{ $history->user->name }}
                                                    </p>
                                                @endif
                                            </div>

                                            <span class="whitespace-nowrap text-xs text-gray-400">
                                                {{ $history->created_at ? $history->created_at->format($dateFormat) : '-' }}
                                            </span>
                                        </div>

                                        @if ($history->old_from_date || $history->old_to_date || $history->new_from_date || $history->new_to_date)
                                            <div class="mt-2 text-xs text-gray-600">
                                                @if ($history->old_from_date || $history->old_to_date)
                                                    <div>
                                                        <span class="font-medium">Previous:</span>
                                                        {{ $history->old_from_date ? $history->old_from_date->format($dateFormat) : '-' }}
                                                        -
                                                        {{ $history->old_to_date ? $history->old_to_date->format($dateFormat) : '-' }}
                                                    </div>
                                                @endif

                                                @if ($history->new_from_date || $history->new_to_date)
                                                    <div class="mt-1">
                                                        <span class="font-medium">New:</span>
                                                        {{ $history->new_from_date ? $history->new_from_date->format($dateFormat) : '-' }}
                                                        -
                                                        {{ $history->new_to_date ? $history->new_to_date->format($dateFormat) : '-' }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($history->reason)
                                            <p class="mt-2 text-xs text-gray-600">
                                                {{ $history->reason }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </form>
    </div>
@endsection

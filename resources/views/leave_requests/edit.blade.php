@extends('layouts.master')

@section('page-content')

@php
    /*
     * IMPORTANT:
     * The application uses approved_mode for approval/rejection screens.
     */
    $approvalMode = $approvalMode ?? request()->boolean('approved_mode');
    $action = $action ?? request()->get('action');

    $isApproveAction = $action === 'approve';
    $isRejectAction = $action === 'reject';

    /*
     * fullEdit:
     * --------------------------------------------------------------
     * true  = employee can edit all normal leave fields.
     * false = employee can edit only reason and attachment.
     *
     * This value is determined by the controller.
     */
    $fullEdit = $fullEdit ?? false;

    /*
     * restrictedEdit:
     * --------------------------------------------------------------
     * true = employee can update only reason and attachment.
     *
     * This value is determined by the controller.
     */
    $restrictedEdit = $restrictedEdit ?? false;

    $assignedTo = $leaveRequest->assigned_to ?? [];

    if (is_string($assignedTo)) {
        $assignedTo = json_decode($assignedTo, true) ?? [];
    }

    $assignedTo = is_array($assignedTo) ? $assignedTo : [];

    /*
     * --------------------------------------------------------------
     * Date values
     * --------------------------------------------------------------
     *
     * Normal mode:
     *   Requested dates are editable only when $fullEdit is true.
     *
     * Restricted mode:
     *   Requested dates are displayed as read-only.
     *
     * Approval mode:
     *   Requested dates are read-only.
     *   Approved dates are editable.
     */
    $requestedFromDate = $leaveRequest->requested_from_date;
    $requestedToDate = $leaveRequest->requested_to_date;

    $approvedFromDate =
        $leaveRequest->approved_from_date
        ?? $leaveRequest->requested_from_date;

    $approvedToDate =
        $leaveRequest->approved_to_date
        ?? $leaveRequest->requested_to_date;

    /*
     * Duration shown in the form.
     *
     * Normal full edit:
     *   Requested duration.
     *
     * Restricted edit:
     *   Existing requested duration.
     *
     * Approval mode:
     *   Existing approved duration if available.
     *   Otherwise requested duration.
     */
    $displayDuration = $approvalMode
        ? (
            $leaveRequest->approved_duration !== null
                ? $leaveRequest->approved_duration
                : $leaveRequest->duration
        )
        : $leaveRequest->duration;

    /*
     * Determine selected leave type for attachment requirement.
     */
    $selectedLeaveTypeId = old(
        'leave_type_id',
        $leaveRequest->leave_type_id
    );

    $selectedLeaveType = null;

    if (isset($leaveTypes)) {
        $selectedLeaveType = $leaveTypes->firstWhere(
            'id',
            $selectedLeaveTypeId
        );
    }

    /*
     * In restricted mode the existing leave type must be used.
     */
    if (!$selectedLeaveType && $leaveRequest->leaveType) {
        $selectedLeaveType = $leaveRequest->leaveType;
    }

    $attachmentRequired = $selectedLeaveType
        && $selectedLeaveType->is_file_upload_required;
@endphp

<div class="px-4 py-6 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $approvalMode ? 'Review Leave Request' : 'Edit Leave Request' }}
            </h1>

            <p class="mt-1 text-sm text-gray-500">

                @if($approvalMode)

                    Review the leave request and update, approve or reject it.

                @elseif($restrictedEdit)

                    Update the reason or attachment for this leave request.

                @else

                    Update your leave request details.

                @endif

            </p>
        </div>

        <a
            href="{{ route('leave-requests.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
            Back
        </a>

    </div>

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
    @if(session('success'))

        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>

    @endif

    {{-- Error Message --}}
    @if(session('error'))

        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>

    @endif

    <form
        id="leave-request-form"
        method="POST"
        action="{{ route('leave-requests.update', $leaveRequest->id) }}"
        enctype="multipart/form-data">

        @csrf
        @method('PUT')

        {{-- Approval mode --}}
        @if($approvalMode)

            <input
                type="hidden"
                name="approved_mode"
                value="1">

        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- ======================================================
                 Main Form
            ======================================================= --}}
            <div class="lg:col-span-2">

                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                    {{-- ==================================================
                         Leave Type
                    =================================================== --}}
                    <div class="mb-5">

                        <label
                            for="leave_type_id"
                            class="mb-2 block text-sm font-medium text-gray-700">

                            Leave Type

                            @if($fullEdit || $approvalMode)
                                <span class="text-red-500">*</span>
                            @endif

                        </label>

                        @if($restrictedEdit && !$approvalMode)

                            {{-- Restricted employee edit --}}
                            <div
                                class="block w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm text-gray-700">

                                {{ $selectedLeaveType?->name ?? '-' }}

                            </div>

                        @else

                            <select
                                id="leave_type_id"
                                name="leave_type_id"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                {{ $fullEdit || $approvalMode ? 'required' : '' }}>

                                <option value="">
                                    Select Leave Type
                                </option>

                                @foreach($leaveTypes as $leaveType)

                                    <option
                                        value="{{ $leaveType->id }}"
                                        {{ (string) old(
                                            'leave_type_id',
                                            $leaveRequest->leave_type_id
                                        ) === (string) $leaveType->id
                                            ? 'selected'
                                            : ''
                                        }}>

                                        {{ $leaveType->name }}

                                    </option>

                                @endforeach

                            </select>

                        @endif

                    </div>

                    {{-- ==================================================
                         Type
                    =================================================== --}}
                    <div class="mb-5">

                        <label
                            for="type"
                            class="mb-2 block text-sm font-medium text-gray-700">

                            Type

                            @if($fullEdit || $approvalMode)
                                <span class="text-red-500">*</span>
                            @endif

                        </label>

                        @if($restrictedEdit && !$approvalMode)

                            <div
                                class="block w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm text-gray-700">

                                {{ $leaveRequest->type === 'half_day' ? 'Half Day' : 'Full Day' }}

                            </div>

                        @else

                            <select
                                id="type"
                                name="type"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                {{ $fullEdit || $approvalMode ? 'required' : '' }}>

                                <option
                                    value="full_day"
                                    {{ old(
                                        'type',
                                        $leaveRequest->type
                                    ) === 'full_day'
                                        ? 'selected'
                                        : ''
                                    }}>

                                    Full Day

                                </option>

                                <option
                                    value="half_day"
                                    {{ old(
                                        'type',
                                        $leaveRequest->type
                                    ) === 'half_day'
                                        ? 'selected'
                                        : ''
                                    }}>

                                    Half Day

                                </option>

                            </select>

                        @endif

                    </div>

                    {{-- ==================================================
                         Half Day Type
                    =================================================== --}}
                    <div
                        id="half-day-type-wrapper"
                        class="mb-5
                            {{ old(
                                'type',
                                $leaveRequest->type
                            ) === 'half_day'
                                ? ''
                                : 'hidden'
                            }}">

                        <label
                            for="half_day_type"
                            class="mb-2 block text-sm font-medium text-gray-700">

                            Half Day Type

                            @if($fullEdit || $approvalMode)
                                <span class="text-red-500">*</span>
                            @endif

                        </label>

                        @if($restrictedEdit && !$approvalMode)

                            <div
                                class="block w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm text-gray-700">

                                @if($leaveRequest->half_day_type === 'first_half')

                                    First Half

                                @elseif($leaveRequest->half_day_type === 'second_half')

                                    Second Half

                                @else

                                    -

                                @endif

                            </div>

                        @else

                            <select
                                id="half_day_type"
                                name="half_day_type"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900"
                                {{ $fullEdit || $approvalMode ? '' : '' }}>

                                <option value="">
                                    Select Half Day Type
                                </option>

                                <option
                                    value="first_half"
                                    {{ old(
                                        'half_day_type',
                                        $leaveRequest->half_day_type
                                    ) === 'first_half'
                                        ? 'selected'
                                        : ''
                                    }}>

                                    First Half

                                </option>

                                <option
                                    value="second_half"
                                    {{ old(
                                        'half_day_type',
                                        $leaveRequest->half_day_type
                                    ) === 'second_half'
                                        ? 'selected'
                                        : ''
                                    }}>

                                    Second Half

                                </option>

                            </select>

                        @endif

                    </div>

                    {{-- ==================================================
                         Normal Mode
                    =================================================== --}}
                    @if(!$approvalMode)

                        @if($fullEdit)

                            {{-- ==================================================
                                 Full Employee Edit - Requested Dates
                            =================================================== --}}
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                                {{-- From Date --}}
                                <div>

                                    <label
                                        for="requested_from_date"
                                        class="mb-2 block text-sm font-medium text-gray-700">

                                        From Date

                                        <span class="text-red-500">*</span>

                                    </label>

                                    <input
                                        type="date"
                                        id="requested_from_date"
                                        name="requested_from_date"
                                        value="{{ old(
                                            'requested_from_date',
                                            $requestedFromDate
                                                ? \Carbon\Carbon::parse($requestedFromDate)->format('Y-m-d')
                                                : ''
                                        ) }}"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                        required>

                                </div>

                                {{-- To Date --}}
                                <div>

                                    <label
                                        for="requested_to_date"
                                        class="mb-2 block text-sm font-medium text-gray-700">

                                        To Date

                                        <span class="text-red-500">*</span>

                                    </label>

                                    <input
                                        type="date"
                                        id="requested_to_date"
                                        name="requested_to_date"
                                        value="{{ old(
                                            'requested_to_date',
                                            $requestedToDate
                                                ? \Carbon\Carbon::parse($requestedToDate)->format('Y-m-d')
                                                : ''
                                        ) }}"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                        required>

                                </div>

                            </div>

                        @else

                            {{-- ==================================================
                                 Restricted Employee Edit
                            =================================================== --}}
                            <div class="mb-5 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">

                                <h3 class="mb-3 text-sm font-semibold text-gray-800">
                                    Requested Dates
                                </h3>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                                    {{-- Requested From --}}
                                    <div>

                                        <span class="block text-xs font-medium uppercase text-gray-500">
                                            From Date
                                        </span>

                                        <span class="mt-1 block text-sm font-semibold text-gray-900">

                                            {{ $requestedFromDate
                                                ? \Carbon\Carbon::parse($requestedFromDate)->format('d/m/Y')
                                                : '-'
                                            }}

                                        </span>

                                    </div>

                                    {{-- Requested To --}}
                                    <div>

                                        <span class="block text-xs font-medium uppercase text-gray-500">
                                            To Date
                                        </span>

                                        <span class="mt-1 block text-sm font-semibold text-gray-900">

                                            {{ $requestedToDate
                                                ? \Carbon\Carbon::parse($requestedToDate)->format('d/m/Y')
                                                : '-'
                                            }}

                                        </span>

                                    </div>

                                </div>

                            </div>

                        @endif

                    @else

                        {{-- ==================================================
                             Approval Mode
                        =================================================== --}}

                        {{-- Requested Dates - Read Only --}}
                        <div class="mb-5 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">

                            <h3 class="mb-3 text-sm font-semibold text-gray-800">
                                Requested Dates
                            </h3>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                                {{-- Requested From --}}
                                <div>

                                    <span class="block text-xs font-medium uppercase text-gray-500">
                                        From Date
                                    </span>

                                    <span class="mt-1 block text-sm font-semibold text-gray-900">

                                        {{ $requestedFromDate
                                            ? \Carbon\Carbon::parse($requestedFromDate)->format('d/m/Y')
                                            : '-'
                                        }}

                                    </span>

                                </div>

                                {{-- Requested To --}}
                                <div>

                                    <span class="block text-xs font-medium uppercase text-gray-500">
                                        To Date
                                    </span>

                                    <span class="mt-1 block text-sm font-semibold text-gray-900">

                                        {{ $requestedToDate
                                            ? \Carbon\Carbon::parse($requestedToDate)->format('d/m/Y')
                                            : '-'
                                        }}

                                    </span>

                                </div>

                            </div>

                        </div>

                        {{-- Approved Dates --}}
                        <div class="mb-5">

                            <div class="mb-3">

                                <label class="block text-sm font-semibold text-gray-800">
                                    Approved Dates
                                </label>

                                <p class="mt-1 text-xs text-gray-500">
                                    The approver can change the final approved leave dates before approving.
                                </p>

                            </div>

                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                                {{-- Approved From --}}
                                <div>

                                    <label
                                        for="approved_from_date"
                                        class="mb-2 block text-sm font-medium text-gray-700">

                                        Approved From Date

                                        <span class="text-red-500">*</span>

                                    </label>

                                    <input
                                        type="date"
                                        id="approved_from_date"
                                        name="approved_from_date"
                                        value="{{ old(
                                            'approved_from_date',
                                            $approvedFromDate
                                                ? \Carbon\Carbon::parse($approvedFromDate)->format('Y-m-d')
                                                : ''
                                        ) }}"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                        required>

                                </div>

                                {{-- Approved To --}}
                                <div>

                                    <label
                                        for="approved_to_date"
                                        class="mb-2 block text-sm font-medium text-gray-700">

                                        Approved To Date

                                        <span class="text-red-500">*</span>

                                    </label>

                                    <input
                                        type="date"
                                        id="approved_to_date"
                                        name="approved_to_date"
                                        value="{{ old(
                                            'approved_to_date',
                                            $approvedToDate
                                                ? \Carbon\Carbon::parse($approvedToDate)->format('Y-m-d')
                                                : ''
                                        ) }}"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                        required>

                                </div>

                            </div>

                        </div>

                    @endif

                    {{-- ==================================================
                         Duration
                    =================================================== --}}
                    <div class="mb-5">

                        <label
                            for="duration"
                            class="mb-2 block text-sm font-medium text-gray-700">

                            Duration

                        </label>

                        <input
                            type="text"
                            id="duration"
                            name="duration"
                            value="{{ old(
                                'duration',
                                $displayDuration
                            ) }}"
                            readonly
                            class="block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2.5 text-sm text-gray-900">

                        <p class="mt-1 text-xs text-gray-500">

                            @if($approvalMode)

                                Duration is calculated from the approved dates.
                                The final paid and unpaid days are calculated
                                by the server when the leave is approved.

                            @elseif($fullEdit)

                                Duration is calculated automatically from the
                                selected requested dates.

                            @else

                                Duration cannot be changed for this request.

                            @endif

                            Half-day leave counts as 0.50 day per date.

                        </p>

                    </div>

                    {{-- ==================================================
                         Reason
                    =================================================== --}}
                    <div class="mb-5">

                        <label
                            for="reason"
                            class="mb-2 block text-sm font-medium text-gray-700">

                            Reason

                            <span class="text-red-500">*</span>

                        </label>

                        <textarea
                            id="reason"
                            name="reason"
                            rows="4"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                            required>{{ old('reason', $leaveRequest->reason) }}</textarea>

                    </div>

                    {{-- ==================================================
                         Approver Comment
                    =================================================== --}}
                    @if($approvalMode)

                        <div class="mb-5">

                            <label
                                for="approver_comment"
                                class="mb-2 block text-sm font-medium text-gray-700">

                                Approver Comments

                            </label>

                            <textarea
                                id="approver_comment"
                                name="approver_comment"
                                rows="4"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Enter your comments...">{{ old(
                                    'approver_comment',
                                    $leaveRequest->approver_comment
                                ) }}</textarea>

                        </div>

                    @endif

                    {{-- ==================================================
                         Attachment
                    =================================================== --}}
                    <div class="mb-5">

                        <label
                            for="attachment"
                            class="mb-2 block text-sm font-medium text-gray-700">

                            Attachment

                            @if($attachmentRequired)

                                <span class="text-red-500">*</span>

                            @endif

                        </label>

                        <input
                            type="file"
                            id="attachment"
                            name="attachment"
                            class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-900 file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:text-sm file:font-medium"
                            @if($attachmentRequired && !$leaveRequest->attachment)
                                required
                            @endif>

                        @if($leaveRequest->attachment)

                            <div class="mt-2 text-sm text-gray-600">

                                Current attachment:

                                <a
                                    href="{{ asset('storage/' . $leaveRequest->attachment) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium text-primary-600 hover:underline">

                                    View Attachment

                                </a>

                            </div>

                        @endif

                    </div>

                    {{-- ==================================================
                         Buttons
                    =================================================== --}}
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5">

                        {{-- Cancel --}}
                        <a
                            href="{{ route('leave-requests.index') }}"
                            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">

                            Cancel

                        </a>

                        {{-- ==================================================
                             Normal Mode
                        =================================================== --}}
                        @if(!$approvalMode)

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

                            {{-- ==================================================
                                 Approval Mode
                            =================================================== --}}

                            {{-- Normal Update --}}
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
                            @if($isApproveAction)

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
                            @if($isRejectAction)

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

            {{-- ==========================================================
                 Right Side
            =========================================================== --}}
            <div class="space-y-6">

                {{-- Request Information --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                    <h2 class="mb-4 text-base font-semibold text-gray-900">
                        Request Information
                    </h2>

                    <div class="space-y-4">

                        {{-- Employee --}}
                        <div>

                            <span class="block text-xs font-medium uppercase text-gray-500">
                                Employee
                            </span>

                            <span class="mt-1 block text-sm font-semibold text-gray-900">
                                {{ $leaveRequest->user->name ?? '-' }}
                            </span>

                        </div>

                        {{-- Status --}}
                        <div>

                            <span class="block text-xs font-medium uppercase text-gray-500">
                                Status
                            </span>

                            <span
                                class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                @if($leaveRequest->status === 'approved')
                                    bg-green-100 text-green-800
                                @elseif($leaveRequest->status === 'rejected')
                                    bg-red-100 text-red-800
                                @elseif($leaveRequest->status === 'cancelled')
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

                            <span class="mt-1 block text-sm text-gray-900">

                                {{ $leaveRequest->submitted_at
                                    ? $leaveRequest->submitted_at->format('d/m/Y H:i')
                                    : (
                                        $leaveRequest->created_at
                                            ? $leaveRequest->created_at->format('d/m/Y H:i')
                                            : '-'
                                    )
                                }}

                            </span>

                        </div>

                        {{-- Paid / Unpaid --}}
                        @if(
                            $leaveRequest->status === 'approved'
                            && (
                                $leaveRequest->paid_days !== null
                                || $leaveRequest->unpaid_days !== null
                            )
                        )

                            <div>

                                <span class="block text-xs font-medium uppercase text-gray-500">
                                    Paid Days
                                </span>

                                <span class="mt-1 block text-sm font-semibold text-green-700">

                                    {{ number_format(
                                        (float) ($leaveRequest->paid_days ?? 0),
                                        2
                                    ) }}

                                </span>

                            </div>

                            <div>

                                <span class="block text-xs font-medium uppercase text-gray-500">
                                    Unpaid Days
                                </span>

                                <span class="mt-1 block text-sm font-semibold text-red-700">

                                    {{ number_format(
                                        (float) ($leaveRequest->unpaid_days ?? 0),
                                        2
                                    ) }}

                                </span>

                            </div>

                        @endif

                    </div>

                </div>

                {{-- Leave Balance --}}
                <div
                    id="leave-balance-info"
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                    <h2 class="mb-4 text-base font-semibold text-gray-900">
                        Leave Balance
                    </h2>

                    <div id="leave-balance-content">

                        <p class="text-sm text-gray-500">
                            Leave balance information will be displayed here.
                        </p>

                    </div>

                </div>

                {{-- Leave History --}}
                @if(
                    isset($leaveRequest->histories)
                    && $leaveRequest->histories->count()
                )

                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                        <h2 class="mb-4 text-base font-semibold text-gray-900">
                            Leave History
                        </h2>

                        <div class="space-y-4">

                            @foreach($leaveRequest->histories as $history)

                                <div class="border-l-2 border-gray-200 pl-4">

                                    <div class="flex items-start justify-between gap-3">

                                        <div>

                                            <p class="text-sm font-semibold text-gray-900">
                                                {{ ucwords(str_replace('_', ' ', $history->action)) }}
                                            </p>

                                            @if($history->user)

                                                <p class="mt-1 text-xs text-gray-500">
                                                    By {{ $history->user->name }}
                                                </p>

                                            @endif

                                        </div>

                                        <span class="whitespace-nowrap text-xs text-gray-400">

                                            {{ $history->created_at
                                                ? $history->created_at->format('d/m/Y H:i')
                                                : '-'
                                            }}

                                        </span>

                                    </div>

                                    @if(
                                        $history->old_from_date
                                        || $history->old_to_date
                                        || $history->new_from_date
                                        || $history->new_to_date
                                    )

                                        <div class="mt-2 text-xs text-gray-600">

                                            @if(
                                                $history->old_from_date
                                                || $history->old_to_date
                                            )

                                                <div>

                                                    <span class="font-medium">
                                                        Previous:
                                                    </span>

                                                    {{ $history->old_from_date
                                                        ? $history->old_from_date->format('d/m/Y')
                                                        : '-'
                                                    }}

                                                    -

                                                    {{ $history->old_to_date
                                                        ? $history->old_to_date->format('d/m/Y')
                                                        : '-'
                                                    }}

                                                </div>

                                            @endif

                                            @if(
                                                $history->new_from_date
                                                || $history->new_to_date
                                            )

                                                <div class="mt-1">

                                                    <span class="font-medium">
                                                        New:
                                                    </span>

                                                    {{ $history->new_from_date
                                                        ? $history->new_from_date->format('d/m/Y')
                                                        : '-'
                                                    }}

                                                    -

                                                    {{ $history->new_to_date
                                                        ? $history->new_to_date->format('d/m/Y')
                                                        : '-'
                                                    }}

                                                </div>

                                            @endif

                                        </div>

                                    @endif

                                    @if($history->reason)

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

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
     * --------------------------------------------------------------
     * Page state
     * --------------------------------------------------------------
     */
    const approvalMode = @json((bool) $approvalMode);
    const fullEdit = @json((bool) $fullEdit);
    const restrictedEdit = @json((bool) $restrictedEdit);

    /*
     * --------------------------------------------------------------
     * Form elements
     * --------------------------------------------------------------
     */
    const form =
        document.getElementById('leave-request-form');

    const typeInput =
        document.getElementById('type');

    const halfDayWrapper =
        document.getElementById('half-day-type-wrapper');

    const halfDayTypeInput =
        document.getElementById('half_day_type');

    const durationInput =
        document.getElementById('duration');

    /*
     * --------------------------------------------------------------
     * Date inputs
     * --------------------------------------------------------------
     *
     * Normal full edit:
     * requested_from_date / requested_to_date
     *
     * Approval mode:
     * approved_from_date / approved_to_date
     *
     * Restricted edit:
     * No editable date inputs.
     */
    let fromDate = null;
    let toDate = null;

    if (approvalMode) {

        fromDate =
            document.getElementById('approved_from_date');

        toDate =
            document.getElementById('approved_to_date');

    } else if (fullEdit) {

        fromDate =
            document.getElementById('requested_from_date');

        toDate =
            document.getElementById('requested_to_date');

    }

    /*
     * --------------------------------------------------------------
     * Toggle half-day field
     * --------------------------------------------------------------
     */
    function toggleHalfDayType() {

        if (!typeInput || !halfDayWrapper) {
            return;
        }

        if (typeInput.value === 'half_day') {

            halfDayWrapper.classList.remove('hidden');

            if (halfDayTypeInput) {

                halfDayTypeInput.required =
                    approvalMode || fullEdit;

            }

        } else {

            halfDayWrapper.classList.add('hidden');

            if (halfDayTypeInput) {
                halfDayTypeInput.required = false;
            }

        }
    }

    /*
     * --------------------------------------------------------------
     * Calculate duration
     * --------------------------------------------------------------
     */
    function calculateDuration() {

        /*
         * Restricted edit does not have editable dates.
         * Keep the existing duration.
         */
        if (
            restrictedEdit
            && !approvalMode
            && !fullEdit
        ) {
            return;
        }

        if (
            !fromDate
            || !toDate
            || !durationInput
        ) {
            return;
        }

        if (
            !fromDate.value
            || !toDate.value
        ) {

            durationInput.value = '';

            return;
        }

        const start =
            new Date(
                fromDate.value + 'T00:00:00'
            );

        const end =
            new Date(
                toDate.value + 'T00:00:00'
            );

        if (
            isNaN(start.getTime())
            || isNaN(end.getTime())
        ) {

            durationInput.value = '';

            return;
        }

        if (end < start) {

            durationInput.value = '';

            return;
        }

        const difference =
            Math.round(
                (
                    end.getTime()
                    - start.getTime()
                ) /
                (
                    1000
                    * 60
                    * 60
                    * 24
                )
            ) + 1;

        let duration = difference;

        if (
            typeInput
            && typeInput.value === 'half_day'
        ) {

            duration =
                difference * 0.5;

        }

        durationInput.value =
            Number(duration).toFixed(2);
    }

    /*
     * --------------------------------------------------------------
     * Validate date range
     * --------------------------------------------------------------
     */
    function validateDates() {

        /*
         * Restricted employee edit has no date changes.
         */
        if (
            restrictedEdit
            && !approvalMode
            && !fullEdit
        ) {
            return true;
        }

        if (!fromDate || !toDate) {
            return true;
        }

        if (
            !fromDate.value
            || !toDate.value
        ) {
            return true;
        }

        if (toDate.value < fromDate.value) {

            alert(
                'To Date cannot be earlier than From Date.'
            );

            toDate.focus();

            return false;
        }

        return true;
    }

    /*
     * --------------------------------------------------------------
     * Initial state
     * --------------------------------------------------------------
     */
    toggleHalfDayType();
    calculateDuration();

    /*
     * --------------------------------------------------------------
     * Type changed
     * --------------------------------------------------------------
     */
    if (typeInput) {

        typeInput.addEventListener(
            'change',
            function () {

                toggleHalfDayType();
                calculateDuration();

            }
        );

    }

    /*
     * --------------------------------------------------------------
     * From date changed
     * --------------------------------------------------------------
     */
    if (fromDate) {

        fromDate.addEventListener(
            'change',
            function () {

                calculateDuration();

            }
        );

    }

    /*
     * --------------------------------------------------------------
     * To date changed
     * --------------------------------------------------------------
     */
    if (toDate) {

        toDate.addEventListener(
            'change',
            function () {

                calculateDuration();

            }
        );

    }

    /*
     * --------------------------------------------------------------
     * Half-day type changed
     * --------------------------------------------------------------
     */
    if (halfDayTypeInput) {

        halfDayTypeInput.addEventListener(
            'change',
            function () {

                calculateDuration();

            }
        );

    }

    /*
     * --------------------------------------------------------------
     * Form submission
     * --------------------------------------------------------------
     */
    if (form) {

        form.addEventListener(
            'submit',
            function (event) {

                /*
                 * Validate date range on the client.
                 */
                if (!validateDates()) {

                    event.preventDefault();

                    return;

                }

                /*
                 * Recalculate duration only when dates are
                 * actually editable / approval dates are used.
                 *
                 * The controller remains responsible for the
                 * authoritative duration calculation.
                 */
                if (
                    approvalMode
                    || fullEdit
                ) {

                    calculateDuration();

                }

            }
        );

    }

});
</script>

@endpush

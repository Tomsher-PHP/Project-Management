@php
    $isCreate = $isCreate ?? false;
    $approvalMode = $approvalMode ?? false;
    $fullEdit = $fullEdit ?? false;
    $restrictedEdit = $restrictedEdit ?? false;

    $leaveRequest = $leaveRequest ?? null;

    $selectedLeaveTypeId = old('leave_type_id', $leaveRequest?->leave_type_id);
    $selectedLeaveType = isset($leaveTypes) ? $leaveTypes->firstWhere('id', $selectedLeaveTypeId) : null;

    if (!$selectedLeaveType && $leaveRequest?->leaveType) {
        $selectedLeaveType = $leaveRequest->leaveType;
    }

    $attachmentRequired = (bool) ($selectedLeaveType?->is_file_upload_required ?? false);

    $existingAttachment = $leaveRequest && $leaveRequest->attachments ? $leaveRequest->attachments->where('is_active', true)->first() : null;

    $requestedFromDate = $leaveRequest?->requested_from_date;
    $requestedToDate = $leaveRequest?->requested_to_date;

    $approvedFromDate = $leaveRequest?->approved_from_date ?? $requestedFromDate;
    $approvedToDate = $leaveRequest?->approved_to_date ?? $requestedToDate;

    $displayDuration = $isCreate ? '0.00' : ($approvalMode ? ($leaveRequest->approved_duration !== null ? $leaveRequest->approved_duration : $leaveRequest->duration) : $leaveRequest->duration);

    $returnToWorkDate = old('return_to_work_date', $leaveRequest?->return_to_work_date ? \Carbon\Carbon::parse($leaveRequest->return_to_work_date)->format('Y-m-d') : '');

    $inputClass = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white';

    $selectClass = 'tom-select w-full border-gray-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white';

    $labelClass = 'mb-2 block text-sm font-medium text-gray-700 dark:text-bgray-50';

    $readonlyClass = 'block w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm text-gray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200';

    $attachmentInputClass = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:file:bg-gray-100 dark:file:text-gray-700';

    $canEditFields = $isCreate || $fullEdit || $approvalMode;
@endphp

<div class="{{ $isCreate ? 'grid grid-cols-1 gap-5 md:grid-cols-2' : '' }}">

    {{-- Leave Type --}}
    <div class="{{ $isCreate ? 'flex flex-col gap-2' : 'mb-5' }}">
        <label for="leave_type_id" class="{{ $labelClass }}">
            Leave Type
            @if ($canEditFields)
                <x-red-star />
            @endif
        </label>

        @if ($restrictedEdit && !$approvalMode && !$isCreate)
            <div class="{{ $readonlyClass }}">
                {{ $selectedLeaveType?->name ?? '-' }}
            </div>
        @else
            <select id="leave_type_id" name="leave_type_id" class="{{ $selectClass }}" {{ $canEditFields ? 'required' : '' }}>
                <option value="">Select Leave Type</option>
                @foreach ($leaveTypes as $leaveType)
                    <option value="{{ $leaveType->id }}" data-file-required="{{ $leaveType->is_file_upload_required ? '1' : '0' }}" {{ (string) $selectedLeaveTypeId === (string) $leaveType->id ? 'selected' : '' }}>
                        {{ $leaveType->name }}
                    </option>
                @endforeach
            </select>
        @endif

        @error('leave_type_id')
            <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
        @enderror
    </div>

    {{-- Leave Type: Full Day / Half Day --}}
    <div class="{{ $isCreate ? 'flex flex-col gap-2' : 'mb-5' }}">
        <label for="type" class="{{ $labelClass }}">
            Type
            @if ($canEditFields)
                <x-red-star />
            @endif
        </label>

        @if ($restrictedEdit && !$approvalMode && !$isCreate)
            <div class="{{ $readonlyClass }}">
                {{ $leaveRequest->type === 'half_day' ? 'Half Day' : 'Full Day' }}
            </div>
        @else
            <select id="type" name="type" class="{{ $selectClass }} tom-select-no-search" {{ $canEditFields ? 'required' : '' }}>
                <option value="">Select Type</option>
                <option value="full_day" {{ old('type', $leaveRequest?->type ?? 'full_day') === 'full_day' ? 'selected' : '' }}>
                    Full Day
                </option>
                <option value="half_day" {{ old('type', $leaveRequest?->type) === 'half_day' ? 'selected' : '' }}>
                    Half Day
                </option>
            </select>
        @endif

        @error('type')
            <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
        @enderror
    </div>

    {{-- Half Day Type --}}
    <div id="half-day-type-wrapper" class="{{ $isCreate ? 'hidden' : 'mb-5' }} {{ old('type', $leaveRequest?->type) === 'half_day' ? '' : 'hidden' }}">

        <label for="half_day_type" class="{{ $labelClass }}">
            {{ $isCreate ? 'Leave Period' : 'Half Day Type' }}
            @if ($canEditFields)
                <x-red-star />
            @endif
        </label>

        @if ($restrictedEdit && !$approvalMode && !$isCreate)
            <div class="{{ $readonlyClass }}">
                @if ($leaveRequest->half_day_type === 'first_half')
                    First Half
                @elseif ($leaveRequest->half_day_type === 'second_half')
                    Second Half
                @else
                    -
                @endif
            </div>
        @else
            <select id="half_day_type" name="half_day_type" class="{{ $selectClass }} tom-select-no-search">
                <option value="">Select Period</option>
                <option value="first_half" {{ old('half_day_type', $leaveRequest?->half_day_type) === 'first_half' ? 'selected' : '' }}>
                    First Half
                </option>
                <option value="second_half" {{ old('half_day_type', $leaveRequest?->half_day_type) === 'second_half' ? 'selected' : '' }}>
                    Second Half
                </option>
            </select>
        @endif

        @error('half_day_type')
            <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
        @enderror
    </div>

    {{-- Date and Duration Fields --}}
    <div class="{{ $isCreate ? 'md:col-span-2' : '' }}">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">

            {{-- From Date --}}
            <div>
                @if ($approvalMode)
                    <label for="approved_from_date" class="{{ $labelClass }}">
                        From Date <x-red-star />
                    </label>
                    <input type="date" id="approved_from_date" name="approved_from_date" value="{{ old('approved_from_date', $approvedFromDate ? \Carbon\Carbon::parse($approvedFromDate)->format('Y-m-d') : '') }}" required class="{{ $inputClass }} datepicker">
                    @error('approved_from_date')
                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                    @enderror
                @elseif ($isCreate || $fullEdit)
                    <label for="requested_from_date" class="{{ $labelClass }}">
                        From Date <x-red-star />
                    </label>
                    <input type="date" id="requested_from_date" name="requested_from_date" value="{{ old('requested_from_date', $requestedFromDate ? \Carbon\Carbon::parse($requestedFromDate)->format('Y-m-d') : '') }}" required class="{{ $inputClass }} datepicker">
                    @error('requested_from_date')
                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                    @enderror
                @else
                    <label class="{{ $labelClass }}">From Date</label>
                    <div class="{{ $readonlyClass }}">
                        {{ $requestedFromDate ? \Carbon\Carbon::parse($requestedFromDate)->format('d/m/Y') : '-' }}
                    </div>
                @endif
            </div>

            {{-- To Date --}}
            <div>
                @if ($approvalMode)
                    <label for="approved_to_date" class="{{ $labelClass }}">
                        To Date <x-red-star />
                    </label>
                    <input type="date" id="approved_to_date" name="approved_to_date" value="{{ old('approved_to_date', $approvedToDate ? \Carbon\Carbon::parse($approvedToDate)->format('Y-m-d') : '') }}" required class="{{ $inputClass }} datepicker">
                    @error('approved_to_date')
                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                    @enderror
                @elseif ($isCreate || $fullEdit)
                    <label for="requested_to_date" class="{{ $labelClass }}">
                        To Date <x-red-star />
                    </label>
                    <input type="date" id="requested_to_date" name="requested_to_date" value="{{ old('requested_to_date', $requestedToDate ? \Carbon\Carbon::parse($requestedToDate)->format('Y-m-d') : '') }}" required class="{{ $inputClass }} datepicker">
                    @error('requested_to_date')
                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                    @enderror
                @else
                    <label class="{{ $labelClass }}">To Date</label>
                    <div class="{{ $readonlyClass }}">
                        {{ $requestedToDate ? \Carbon\Carbon::parse($requestedToDate)->format('d/m/Y') : '-' }}
                    </div>
                @endif
            </div>

            {{-- Return to Work Date --}}
            <div>
                <label for="return_to_work_date" class="{{ $labelClass }}">
                    Return Date
                </label>

                @if ($restrictedEdit && !$approvalMode && !$isCreate)
                    <div class="{{ $readonlyClass }}">
                        {{ $returnToWorkDate ? \Carbon\Carbon::parse($returnToWorkDate)->format('d/m/Y') : '-' }}
                    </div>
                @else
                    <input type="date" id="return_to_work_date" name="return_to_work_date" value="{{ $returnToWorkDate }}" class="{{ $inputClass }} datepicker">
                @endif

                @error('return_to_work_date')
                    <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            {{-- Number of Days --}}
            <div>
                <label for="{{ $isCreate ? 'duration_display' : 'duration' }}" class="{{ $labelClass }}">
                    Number of Days
                </label>

                <input type="text" id="{{ $isCreate ? 'duration_display' : 'duration' }}" @if (!$isCreate) name="duration" @endif value="{{ old('duration', $displayDuration) }}" readonly class="{{ $readonlyClass }}">

                <p class="mt-1 text-xs text-gray-500 dark:text-bgray-300">
                    Automatically calculated.
                </p>
            </div>

        </div>

        @if ($approvalMode)
            <div class="mt-3 rounded-lg bg-gray-50 p-3 text-xs text-gray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                <strong>Requested Dates:</strong>
                {{ $requestedFromDate ? \Carbon\Carbon::parse($requestedFromDate)->format('d/m/Y') : '-' }}
                -
                {{ $requestedToDate ? \Carbon\Carbon::parse($requestedToDate)->format('d/m/Y') : '-' }}
            </div>
        @endif

        @if ($restrictedEdit && !$approvalMode && !$isCreate)
            <p class="mt-2 text-xs text-gray-500 dark:text-bgray-300">
                Number of Days cannot be changed for this request.
            </p>
        @elseif ($approvalMode)
            <p class="mt-2 text-xs text-gray-500 dark:text-bgray-300">
                Number of Days is calculated from the approved dates. The final paid and unpaid days are calculated by the server when the leave is approved.
            </p>
        @endif

        <p class="mt-1 text-xs text-gray-500 dark:text-bgray-300">
            Half-day leave counts as 0.50 day per date.
        </p>
    </div>

    {{-- Create Leave Balance Information --}}
    @if ($isCreate)
        <div id="leave-balance-info" class="hidden md:col-span-2">
            <div id="leave-balance-success" class="hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <strong>Leave Balance:</strong>
                <span id="leave-balance-success-text"></span>
            </div>

            <div id="leave-balance-warning" class="hidden rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.1 12.28A2 2 0 004.93 19h14.14l-7.1-12.28a2 2 0 00-3.42 0z" />
                    </svg>
                    <div>
                        <strong>Leave Balance Notice</strong>
                        <p id="leave-balance-warning-text" class="mt-1"></p>
                        <p class="mt-2 text-xs">
                            You can still submit the request. The final leave balance will be checked when the request is approved.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reason --}}
    <div class="{{ $isCreate ? 'md:col-span-2' : 'mb-5' }}">
        <label for="reason" class="{{ $labelClass }}">
            Reason <x-red-star />
        </label>

        <textarea id="reason" name="reason" rows="{{ $isCreate ? 5 : 4 }}" required placeholder="{{ $isCreate ? 'Enter the reason for your leave...' : '' }}" class="{{ $inputClass }}">{{ old('reason', $leaveRequest?->reason) }}</textarea>

        @error('reason')
            <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
        @enderror
    </div>

    {{-- Approver Comments --}}
    @if ($approvalMode)
        <div class="mb-5 md:col-span-2">
            <label for="approver_comment" class="{{ $labelClass }}">
                Approver Comments
            </label>
            <textarea id="approver_comment" name="approver_comment" rows="4" class="{{ $inputClass }}" placeholder="Enter your comments...">{{ old('approver_comment', $leaveRequest?->approver_comment) }}</textarea>
            @error('approver_comment')
                <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
            @enderror
        </div>
    @endif

    {{-- Attachment --}}
    <div id="attachment-wrapper" class="{{ $isCreate ? 'hidden' : 'mb-5' }}">
        <label for="attachment" class="{{ $labelClass }}">
            {{ $isCreate ? 'Supporting Document' : 'Attachment' }}
            <span id="attachment-required" class="{{ $attachmentRequired && !$existingAttachment ? '' : 'hidden' }} text-red-500">*</span>
        </label>

        <input type="file" id="attachment" name="attachment" class="{{ $attachmentInputClass }}" @if ($attachmentRequired && !$existingAttachment) required @endif>

        <p id="attachment-help" class="mt-2 text-xs text-bgray-500 dark:text-bgray-300">
            @if ($attachmentRequired && !$existingAttachment)
                A supporting document is required for this leave type.
            @else
                You may upload a supporting document if required.
            @endif
        </p>

        @if ($existingAttachment)
            <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-darkblack-400 dark:bg-darkblack-500">
                <div class="text-sm font-medium text-gray-700 dark:text-white">Current attachment</div>
                <div class="mt-2 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm text-gray-600 dark:text-bgray-300">
                            {{ $existingAttachment->original_name ?? ($existingAttachment->file_name ?? 'Attachment') }}
                        </p>
                    </div>
                    <a href="{{ asset('storage/' . ltrim($existingAttachment->file_path, '/')) }}" target="_blank" rel="noopener noreferrer" class="{{ $labelClass }}">
                        View Attachment
                    </a>
                </div>
            </div>
        @elseif ($attachmentRequired && !$isCreate)
            <p class="mt-1 text-xs text-red-600">
                An attachment is required for this leave type.
            </p>
        @endif

        @error('attachment')
            <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
        @enderror
    </div>
</div>

@once
    @push('scripts')
        <style>
            #leave_type_id+.ts-wrapper .ts-control,
            #type+.ts-wrapper .ts-control,
            #half_day_type+.ts-wrapper .ts-control,
            #leave_type_id+.ts-wrapper .ts-control .placeholder,
            #leave_type_id+.ts-wrapper .ts-control .item,
            #type+.ts-wrapper .ts-control .placeholder,
            #type+.ts-wrapper .ts-control .item,
            #half_day_type+.ts-wrapper .ts-control .placeholder,
            #half_day_type+.ts-wrapper .ts-control .item {
                text-align: left !important;
            }

            #leave_type_id+.ts-wrapper .ts-control,
            #type+.ts-wrapper .ts-control,
            #half_day_type+.ts-wrapper .ts-control {
                background-color: #fff;
                border-color: #d1d5db;
                color: #111827;
                border-radius: 0.5rem;
                padding: 0.625rem 0.75rem;
                min-height: 42px;
                box-shadow: none;
            }

            .dark #leave_type_id+.ts-wrapper .ts-control,
            .dark #type+.ts-wrapper .ts-control,
            .dark #half_day_type+.ts-wrapper .ts-control {
                background-color: #242631;
                border-color: #3b3d4a;
                color: #fff;
            }

            .dark #leave_type_id+.ts-wrapper .ts-control input,
            .dark #type+.ts-wrapper .ts-control input,
            .dark #half_day_type+.ts-wrapper .ts-control input {
                color: #fff;
            }

            .dark #leave_type_id+.ts-wrapper .ts-control .placeholder,
            .dark #type+.ts-wrapper .ts-control .placeholder,
            .dark #half_day_type+.ts-wrapper .ts-control .placeholder {
                color: #a3a3b0;
            }

            .dark #leave_type_id+.ts-wrapper .ts-dropdown,
            .dark #type+.ts-wrapper .ts-dropdown,
            .dark #half_day_type+.ts-wrapper .ts-dropdown {
                background-color: #242631;
                border-color: #3b3d4a;
                color: #fff;
            }

            .dark #leave_type_id+.ts-wrapper .ts-dropdown .option,
            .dark #type+.ts-wrapper .ts-dropdown .option,
            .dark #half_day_type+.ts-wrapper .ts-dropdown .option {
                color: #fff;
            }

            .dark #leave_type_id+.ts-wrapper .ts-dropdown .active,
            .dark #type+.ts-wrapper .ts-dropdown .active,
            .dark #half_day_type+.ts-wrapper .ts-dropdown .active {
                background-color: #3b3d4a;
                color: #fff;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const isCreate = @json((bool) $isCreate);
                const approvalMode = @json((bool) $approvalMode);
                const fullEdit = @json((bool) $fullEdit);
                const restrictedEdit = @json((bool) $restrictedEdit);

                const leaveType = document.getElementById('leave_type_id');
                const type = document.getElementById('type');
                const halfDayWrapper = document.getElementById('half-day-type-wrapper');
                const halfDayType = document.getElementById('half_day_type');

                const attachmentWrapper = document.getElementById('attachment-wrapper');
                const attachment = document.getElementById('attachment');
                const attachmentRequired = document.getElementById('attachment-required');
                const attachmentHelp = document.getElementById('attachment-help');

                const fromDate = document.getElementById(
                    isCreate || (!approvalMode && fullEdit) ?
                    'requested_from_date' :
                    (approvalMode ? 'approved_from_date' : '')
                );

                const toDate = document.getElementById(
                    isCreate || (!approvalMode && fullEdit) ?
                    'requested_to_date' :
                    (approvalMode ? 'approved_to_date' : '')
                );

                const duration = document.getElementById(isCreate ? 'duration_display' : 'duration');

                let balanceCheckTimer = null;

                function updateAttachmentField() {
                    if (!leaveType || !attachmentWrapper || !attachment || !attachmentRequired || !attachmentHelp) {
                        return;
                    }

                    const option = leaveType.options[leaveType.selectedIndex];

                    if (!option || !option.value) {
                        if (isCreate) {
                            attachmentWrapper.classList.add('hidden');
                        }

                        attachmentRequired.classList.add('hidden');
                        attachment.removeAttribute('required');
                        attachmentHelp.textContent = 'You may upload a supporting document if required.';
                        return;
                    }

                    attachmentWrapper.classList.remove('hidden');

                    const required = option.dataset.fileRequired === '1';
                    const hasExistingAttachment = @json((bool) $existingAttachment);

                    if (required && !hasExistingAttachment) {
                        attachmentRequired.classList.remove('hidden');
                        attachment.setAttribute('required', 'required');
                        attachmentHelp.textContent = 'A supporting document is required for this leave type.';
                    } else {
                        attachmentRequired.classList.add('hidden');
                        attachment.removeAttribute('required');
                        attachmentHelp.textContent = 'You may upload a supporting document if required.';
                    }
                }

                function toggleHalfDayType() {
                    if (!type || !halfDayWrapper || !halfDayType) {
                        return;
                    }

                    if (type.value === 'half_day') {
                        halfDayWrapper.classList.remove('hidden');
                        halfDayType.required = isCreate || fullEdit || approvalMode;
                    } else {
                        halfDayWrapper.classList.add('hidden');
                        halfDayType.required = false;
                    }
                }

                function calculateDuration() {
                    if (restrictedEdit && !approvalMode && !fullEdit) {
                        return;
                    }

                    if (!type || !fromDate || !toDate || !duration || !fromDate.value || !toDate.value) {
                        if (duration && (isCreate || approvalMode || fullEdit)) {
                            duration.value = isCreate ? '0.00' : '';
                        }
                        return;
                    }

                    const start = new Date(fromDate.value + 'T00:00:00');
                    const end = new Date(toDate.value + 'T00:00:00');

                    if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) {
                        duration.value = isCreate ? '0.00' : '';
                        return;
                    }

                    const days = Math.round((end.getTime() - start.getTime()) / 86400000) + 1;
                    const calculatedDuration = type.value === 'half_day' ? days * 0.5 : days;

                    duration.value = calculatedDuration.toFixed(2);
                }

                const balanceInfo = document.getElementById('leave-balance-info');
                const balanceSuccess = document.getElementById('leave-balance-success');
                const balanceSuccessText = document.getElementById('leave-balance-success-text');
                const balanceWarning = document.getElementById('leave-balance-warning');
                const balanceWarningText = document.getElementById('leave-balance-warning-text');

                function resetLeaveBalanceInfo() {
                    balanceInfo?.classList.add('hidden');
                    balanceSuccess?.classList.add('hidden');
                    balanceWarning?.classList.add('hidden');

                    if (balanceSuccessText) balanceSuccessText.textContent = '';
                    if (balanceWarningText) balanceWarningText.textContent = '';
                }

                function checkLeaveBalance() {
                    if (!isCreate || !type || !leaveType || !fromDate || !toDate) {
                        return;
                    }

                    const values = {
                        type: type.value,
                        leave_type_id: leaveType.value,
                        requested_from_date: fromDate.value,
                        requested_to_date: toDate.value
                    };

                    if (!values.type || !values.leave_type_id || !values.requested_from_date || !values.requested_to_date || values.requested_to_date < values.requested_from_date) {
                        resetLeaveBalanceInfo();
                        return;
                    }

                    clearTimeout(balanceCheckTimer);

                    balanceCheckTimer = setTimeout(function() {
                        const params = new URLSearchParams(values);

                        fetch(`{{ route('leave-requests.check-balance') }}?${params.toString()}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Unable to check leave balance.');
                                }

                                return response.json();
                            })
                            .then(data => {
                                if (!balanceInfo) return;

                                balanceInfo.classList.remove('hidden');

                                if (!data.has_balance_record) {
                                    balanceSuccess?.classList.add('hidden');
                                    balanceWarning?.classList.remove('hidden');

                                    if (balanceWarningText) {
                                        balanceWarningText.textContent = data.message;
                                    }

                                    return;
                                }

                                if (data.eligible) {
                                    balanceWarning?.classList.add('hidden');
                                    balanceSuccess?.classList.remove('hidden');

                                    if (balanceSuccessText) {
                                        balanceSuccessText.textContent =
                                            `${data.leave_type_name}: ${Number(data.current_balance).toFixed(2)} days available. Request duration: ${Number(data.duration).toFixed(2)} days.`;
                                    }
                                } else {
                                    balanceSuccess?.classList.add('hidden');
                                    balanceWarning?.classList.remove('hidden');

                                    if (balanceWarningText) {
                                        balanceWarningText.textContent = data.message;
                                    }
                                }
                            })
                            .catch(resetLeaveBalanceInfo);
                    }, 300);
                }

                leaveType?.addEventListener('change', function() {
                    updateAttachmentField();
                    checkLeaveBalance();
                });

                type?.addEventListener('change', function() {
                    toggleHalfDayType();
                    calculateDuration();
                    checkLeaveBalance();
                });

                fromDate?.addEventListener('change', function() {
                    calculateDuration();
                    checkLeaveBalance();
                });

                toDate?.addEventListener('change', function() {
                    calculateDuration();
                    checkLeaveBalance();
                });

                halfDayType?.addEventListener('change', calculateDuration);

                updateAttachmentField();
                toggleHalfDayType();
                calculateDuration();

                if (isCreate) {
                    checkLeaveBalance();
                }

                const form = document.getElementById(isCreate ? 'create-leave-request-form' : 'leave-request-form');

                let submitting = false;
                let bypassConfirmation = false;

                form?.addEventListener('submit', async function(event) {
                    if (bypassConfirmation) {
                        bypassConfirmation = false;
                        return;
                    }

                    if (
                        fromDate && toDate &&
                        fromDate.value && toDate.value &&
                        toDate.value < fromDate.value
                    ) {
                        event.preventDefault();

                        Alert.error({
                            title: 'Invalid Date',
                            text: 'To Date cannot be earlier than From Date.'
                        });

                        toDate.focus();
                        return;
                    }

                    if (isCreate || approvalMode || fullEdit) {
                        calculateDuration();
                    }

                    const submitter = event.submitter;
                    const action = submitter?.name === 'action'
                        ? submitter.value
                        : '';

                    const isCreateConfirmation =
                        isCreate && form.hasAttribute('data-unpaid-leave-confirm');

                    const isApprovalConfirmation =
                        approvalMode && action === 'update_and_approve';

                    if (!isCreateConfirmation && !isApprovalConfirmation) {
                        return;
                    }

                    event.preventDefault();

                    if (submitting) {
                        return;
                    }

                    submitting = true;

                    try {
                        const formData = new FormData(form);

                        if (submitter?.name) {
                            formData.set(submitter.name, submitter.value);
                        }

                        // Read the actual form action attribute, not form.action.
                        const formUrl = form.getAttribute('action');

                        const response = await fetch(formUrl, {
                            method: (form.getAttribute('method') || 'POST').toUpperCase(),
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        let data = {};

                        try {
                            data = await response.json();
                        } catch (error) {
                            // Response may contain HTML after a successful redirect.
                        }

                        if (response.status === 422 && data.requires_confirmation) {
                            submitting = false;

                            const result = await Alert.confirm({
                                title: 'Unpaid Leave Confirmation',
                                text: data.message,
                                confirmText: 'Proceed',
                                cancelText: 'Cancel'
                            });

                            const confirmed =
                                result === true ||
                                result?.isConfirmed === true ||
                                result?.confirmed === true;

                            if (!confirmed) {
                                if (isApprovalConfirmation && data.cancel_url) {
                                    window.location.href = data.cancel_url;
                                }

                                return;
                            }

                            let confirmationInput = form.querySelector(
                                '[name="confirm_unpaid_leave"]'
                            );

                            if (!confirmationInput) {
                                confirmationInput = document.createElement('input');
                                confirmationInput.type = 'hidden';
                                confirmationInput.name = 'confirm_unpaid_leave';
                                form.appendChild(confirmationInput);
                            }

                            confirmationInput.value = '1';

                            submitting = true;
                            bypassConfirmation = true;

                            if (submitter && submitter.form === form) {
                                form.requestSubmit(submitter);
                            } else {
                                form.requestSubmit();
                            }

                            return;
                        }

                        if (response.redirected) {
                            window.location.href = response.url;
                            return;
                        }

                        if (response.status === 422) {
                            submitting = false;

                            Alert.error({
                                title: 'Validation Error',
                                text: data.message || 'Please check the submitted information.'
                            });

                            return;
                        }

                        if (!response.ok) {
                            throw new Error(
                                data.message || 'Unable to submit the leave request.'
                            );
                        }

                        if (response.url) {
                            window.location.href = response.url;
                        }

                    } catch (error) {
                        submitting = false;
                        bypassConfirmation = false;

                        Alert.error({
                            title: 'Error',
                            text: error.message || 'Something went wrong. Please try again.'
                        });
                    }
                });
            });
        </script>
    @endpush
@endonce

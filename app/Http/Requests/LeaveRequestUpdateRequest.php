<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LeaveRequestUpdateRequest extends FormRequest
{
    /**
     * Authorize the request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the LeaveRequest being updated.
     */
    protected function getLeaveRequest(): ?LeaveRequest
    {
        /*
         * Your route may use either:
         *
         * {leave_request}
         *
         * or
         *
         * {id}
         *
         * Support both.
         */
        $leaveRequest =
            $this->route('leave_request')
            ?? $this->route('leaveRequest')
            ?? $this->route('id');

        /*
         * Laravel route model binding may already give us
         * the LeaveRequest model.
         */
        if ($leaveRequest instanceof LeaveRequest) {
            return $leaveRequest;
        }

        /*
         * If only the ID was supplied, load the model.
         */
        if ($leaveRequest) {
            return LeaveRequest::find($leaveRequest);
        }

        return null;
    }

    /**
     * Determine whether this request is approval/rejection mode.
     */
    protected function isApprovalMode(): bool
    {
        $action = $this->input('action');

        $isApprovalAction = in_array(
            $action,
            [
                'approve',
                'reject',
                'update_and_approve',
            ],
            true
        );

        return $this->boolean('approved_mode')
            || $isApprovalAction;
    }

    /**
     * Determine whether the authenticated user owns this request.
     */
    protected function isOwner(
        ?LeaveRequest $leaveRequest
    ): bool {
        if (
            !$leaveRequest
            || !auth()->check()
        ) {
            return false;
        }

        return (int) $leaveRequest->user_id
            === (int) auth()->id();
    }

    /**
     * Determine whether the employee has full edit access.
     *
     * Full edit:
     *
     * - employee owns request
     * - not approval mode
     * - request is pending
     * - added_by is NULL OR added_by is the same employee
     */
    protected function isFullEdit(
        ?LeaveRequest $leaveRequest
    ): bool {
        if (!$leaveRequest) {
            return false;
        }

        /*
         * Approval/review mode is never full employee edit.
         */
        if ($this->isApprovalMode()) {
            return false;
        }

        /*
         * Only the employee who needs the leave can perform
         * normal employee editing.
         */
        if (!$this->isOwner($leaveRequest)) {
            return false;
        }

        /*
         * Full edit is only available while pending.
         */
        if ($leaveRequest->status !== 'pending') {
            return false;
        }

        /*
         * NULL added_by is treated as employee-created for
         * older records created before added_by existed.
         */
        if (
            $leaveRequest->added_by !== null
            && (int) $leaveRequest->added_by
                !== (int) auth()->id()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the employee is in restricted edit mode.
     *
     * Restricted edit:
     *
     * - employee owns request
     * - not approval mode
     * - request is pending or approved
     * - full edit is not allowed
     *
     * Editable fields:
     *
     * - reason
     * - attachment
     * - return_to_work_date
     */
    protected function isRestrictedEdit(
        ?LeaveRequest $leaveRequest
    ): bool {
        if (!$leaveRequest) {
            return false;
        }

        /*
         * Approval mode uses a different validation set.
         */
        if ($this->isApprovalMode()) {
            return false;
        }

        /*
         * Only the employee who needs the leave can perform
         * restricted employee editing.
         */
        if (!$this->isOwner($leaveRequest)) {
            return false;
        }

        /*
         * Approved and pending requests are editable in
         * restricted mode.
         */
        if (
            !in_array(
                $leaveRequest->status,
                [
                    'pending',
                    'approved',
                ],
                true
            )
        ) {
            return false;
        }

        /*
         * If full edit is available, this is not restricted mode.
         */
        return !$this->isFullEdit($leaveRequest);
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        $leaveRequest = $this->getLeaveRequest();

        $action = $this->input('action');

        $approvalMode = $this->isApprovalMode();

        $fullEdit = $this->isFullEdit(
            $leaveRequest
        );

        $restrictedEdit = $this->isRestrictedEdit(
            $leaveRequest
        );

        /*
         * ==========================================================
         * RESTRICTED EMPLOYEE EDIT
         * ==========================================================
         *
         * When an employee edits:
         *
         * - approved leave
         * - pending leave added by manager/reporter/Super Admin
         *
         * The employee can update:
         *
         * - reason
         * - attachment
         * - return_to_work_date
         *
         * The original leave dates and leave details remain unchanged.
         */
        if ($restrictedEdit) {

            return [

                /*
                 * Action
                 */
                'action' => [
                    'required',
                    Rule::in([
                        'update',
                    ]),
                ],

                /*
                 * Reason
                 */
                'reason' => [
                    'required',
                    'string',
                    'max:2000',
                ],

                /*
                 * Attachment
                 *
                 * Optional unless the existing leave type requires
                 * an attachment and there is no existing attachment.
                 */
                'attachment' => [
                    'nullable',
                    'file',
                    'max:10240',
                ],

                /*
                 * Expected Return-to-Work Date
                 *
                 * Employees can update or clear this field.
                 *
                 * The comparison against the applicable leave end date
                 * is handled in withValidator().
                 */
                'return_to_work_date' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],

                /*
                 * These fields are intentionally NOT required.
                 *
                 * If the Blade does not send them, validation
                 * will still succeed.
                 *
                 * The controller must not update these fields.
                 */
                'leave_type_id' => [
                    'sometimes',
                    'nullable',
                    'exists:leave_types,id',
                ],

                'type' => [
                    'sometimes',
                    'nullable',
                    'in:full_day,half_day',
                ],

                'half_day_type' => [
                    'sometimes',
                    'nullable',
                    Rule::in([
                        'first_half',
                        'second_half',
                    ]),
                ],

                'requested_from_date' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],

                'requested_to_date' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],

                /*
                 * Employee must never modify approved dates.
                 */
                'approved_from_date' => [
                    'prohibited',
                ],

                'approved_to_date' => [
                    'prohibited',
                ],

                /*
                 * Employee cannot modify approver comments.
                 */
                'approver_comment' => [
                    'prohibited',
                ],
            ];
        }

        /*
         * ==========================================================
         * FULL EMPLOYEE EDIT
         * ==========================================================
         *
         * Pending request created by the employee.
         */
        if ($fullEdit) {

            return [

                /*
                 * Action
                 */
                'action' => [
                    'required',
                    Rule::in([
                        'update',
                    ]),
                ],

                /*
                 * Leave type
                 */
                'leave_type_id' => [
                    'required',
                    'exists:leave_types,id',
                ],

                /*
                 * Full / Half day
                 */
                'type' => [
                    'required',
                    'in:full_day,half_day',
                ],

                /*
                 * Half-day period
                 */
                'half_day_type' => [
                    'nullable',
                    'required_if:type,half_day',
                    Rule::in([
                        'first_half',
                        'second_half',
                    ]),
                ],

                /*
                 * Requested dates
                 */
                'requested_from_date' => [
                    'required',
                    'date',
                ],

                'requested_to_date' => [
                    'required',
                    'date',
                    'after_or_equal:requested_from_date',
                ],

                /*
                 * Expected Return-to-Work Date
                 */
                'return_to_work_date' => [
                    'nullable',
                    'date',
                ],

                /*
                 * Employee cannot submit approved dates.
                 */
                'approved_from_date' => [
                    'prohibited',
                ],

                'approved_to_date' => [
                    'prohibited',
                ],

                /*
                 * Reason
                 */
                'reason' => [
                    'required',
                    'string',
                    'max:2000',
                ],

                /*
                 * Attachment
                 */
                'attachment' => [
                    'nullable',
                    'file',
                    'max:10240',
                ],

                /*
                 * Approver comment cannot be changed by employee.
                 */
                'approver_comment' => [
                    'prohibited',
                ],
            ];
        }

        /*
         * ==========================================================
         * APPROVAL / REVIEW MODE
         * ==========================================================
         *
         * Supported actions:
         *
         * update
         * update_and_approve
         * approve
         * reject
         */
        if ($approvalMode) {

            $requiresApprovedDates = in_array(
                $action,
                [
                    'approve',
                    'update_and_approve',
                ],
                true
            );

            return [

                /*
                 * Action
                 */
                'action' => [
                    'required',
                    Rule::in([
                        'update',
                        'update_and_approve',
                        'approve',
                        'reject',
                    ]),
                ],

                /*
                 * Leave type
                 */
                'leave_type_id' => [
                    'required',
                    'exists:leave_types,id',
                ],

                /*
                 * Full / Half day
                 */
                'type' => [
                    'required',
                    'in:full_day,half_day',
                ],

                /*
                 * Half-day period
                 */
                'half_day_type' => [
                    'nullable',
                    'required_if:type,half_day',
                    Rule::in([
                        'first_half',
                        'second_half',
                    ]),
                ],

                /*
                 * Requested dates are read-only during approval.
                 *
                 * They are allowed to be absent because the controller
                 * uses the existing requested dates.
                 */
                'requested_from_date' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],

                'requested_to_date' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],

                /*
                 * Approved dates.
                 *
                 * Required only when approving.
                 */
                'approved_from_date' => [
                    $requiresApprovedDates
                        ? 'required'
                        : 'nullable',

                    'date',
                ],

                'approved_to_date' => [
                    $requiresApprovedDates
                        ? 'required'
                        : 'nullable',

                    'date',

                    'after_or_equal:approved_from_date',
                ],

                /*
                 * Expected Return-to-Work Date
                 *
                 * Compared against:
                 * - requested_to_date for action=update
                 * - approved_to_date for approve/update_and_approve
                 */
                'return_to_work_date' => [
                    'nullable',
                    'date',
                ],

                /*
                 * Reason
                 */
                'reason' => [
                    'required',
                    'string',
                    'max:2000',
                ],

                /*
                 * Attachment
                 */
                'attachment' => [
                    'nullable',
                    'file',
                    'max:10240',
                ],

                /*
                 * Approver comment
                 */
                'approver_comment' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ];
        }

        /*
         * ==========================================================
         * FALLBACK
         * ==========================================================
         *
         * Normally the controller authorization should prevent this
         * situation.
         */
        return [

            'action' => [
                'required',
                Rule::in([
                    'update',
                    'update_and_approve',
                    'approve',
                    'reject',
                ]),
            ],

            'leave_type_id' => [
                'required',
                'exists:leave_types,id',
            ],

            'type' => [
                'required',
                'in:full_day,half_day',
            ],

            'half_day_type' => [
                'nullable',
                'required_if:type,half_day',
                Rule::in([
                    'first_half',
                    'second_half',
                ]),
            ],

            'requested_from_date' => [
                'required',
                'date',
            ],

            'requested_to_date' => [
                'required',
                'date',
                'after_or_equal:requested_from_date',
            ],

            'approved_from_date' => [
                'nullable',
                'date',
            ],

            'approved_to_date' => [
                'nullable',
                'date',
                'after_or_equal:approved_from_date',
            ],

            'return_to_work_date' => [
                'nullable',
                'date',
            ],

            'reason' => [
                'required',
                'string',
                'max:2000',
            ],

            'attachment' => [
                'nullable',
                'file',
                'max:10240',
            ],

            'approver_comment' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Additional validation.
     */
    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {

                $leaveRequest = $this->getLeaveRequest();

                if (!$leaveRequest) {
                    return;
                }

                $approvalMode = $this->isApprovalMode();

                $fullEdit = $this->isFullEdit(
                    $leaveRequest
                );

                $restrictedEdit = $this->isRestrictedEdit(
                    $leaveRequest
                );

                /*
                 * ==================================================
                 * RESTRICTED EMPLOYEE EDIT
                 * ==================================================
                 */
                if ($restrictedEdit) {

                    /*
                     * Leave type
                     */
                    if (
                        $this->has('leave_type_id')
                        && $this->input('leave_type_id') !== null
                        && (int) $this->input('leave_type_id')
                            !== (int) $leaveRequest->leave_type_id
                    ) {
                        $validator->errors()->add(
                            'leave_type_id',
                            'Leave type cannot be changed for this request.'
                        );
                    }

                    /*
                     * Type
                     */
                    if (
                        $this->has('type')
                        && $this->input('type') !== null
                        && $this->input('type') !== $leaveRequest->type
                    ) {
                        $validator->errors()->add(
                            'type',
                            'The leave type/day type cannot be changed for this request.'
                        );
                    }

                    /*
                     * Half-day type
                     */
                    if (
                        $this->has('half_day_type')
                        && $this->input('half_day_type') !== null
                        && $this->input('half_day_type')
                            !== $leaveRequest->half_day_type
                    ) {
                        $validator->errors()->add(
                            'half_day_type',
                            'Half-day type cannot be changed for this request.'
                        );
                    }

                    /*
                     * Requested From Date
                     */
                    if ($this->filled('requested_from_date')) {

                        $submittedFromDate = $this->input(
                            'requested_from_date'
                        );

                        $existingFromDate = $this->formatDate(
                            $leaveRequest->requested_from_date
                        );

                        if ($submittedFromDate !== $existingFromDate) {
                            $validator->errors()->add(
                                'requested_from_date',
                                'The requested From Date cannot be changed for this request.'
                            );
                        }
                    }

                    /*
                     * Requested To Date
                     */
                    if ($this->filled('requested_to_date')) {

                        $submittedToDate = $this->input(
                            'requested_to_date'
                        );

                        $existingToDate = $this->formatDate(
                            $leaveRequest->requested_to_date
                        );

                        if ($submittedToDate !== $existingToDate) {
                            $validator->errors()->add(
                                'requested_to_date',
                                'The requested To Date cannot be changed for this request.'
                            );
                        }
                    }

                    /*
                     * Approved dates
                     */
                    if ($this->filled('approved_from_date')) {
                        $validator->errors()->add(
                            'approved_from_date',
                            'Approved dates cannot be changed in employee edit mode.'
                        );
                    }

                    if ($this->filled('approved_to_date')) {
                        $validator->errors()->add(
                            'approved_to_date',
                            'Approved dates cannot be changed in employee edit mode.'
                        );
                    }

                    /*
                     * Approver comment
                     */
                    if ($this->has('approver_comment')) {
                        $validator->errors()->add(
                            'approver_comment',
                            'Approver comments cannot be changed by the employee.'
                        );
                    }

                    /*
                     * Expected Return-to-Work Date
                     *
                     * For approved leave, compare against the approved
                     * end date. Otherwise, use the requested end date.
                     */
                    $effectiveEndDate =
                        $leaveRequest->status === 'approved'
                        && $leaveRequest->approved_to_date
                            ? $leaveRequest->approved_to_date
                            : $leaveRequest->requested_to_date;

                    $this->validateReturnToWorkDate(
                        $validator,
                        $effectiveEndDate
                    );

                    /*
                     * Attachment requirement
                     */
                    $this->validateAttachmentRequirement(
                        $validator,
                        $leaveRequest
                    );

                    return;
                }

                /*
                 * ==================================================
                 * FULL EMPLOYEE EDIT
                 * ==================================================
                 */
                if (
                    $fullEdit
                    && !$approvalMode
                ) {

                    /*
                     * Requested dates are already validated by rules().
                     *
                     * We intentionally allow multi-day half-day leave.
                     */
                    $this->validateReturnToWorkDate(
                        $validator,
                        $this->input('requested_to_date')
                    );

                    $this->validateAttachmentRequirement(
                        $validator,
                        $leaveRequest
                    );

                    return;
                }

                /*
                 * ==================================================
                 * APPROVAL MODE
                 * ==================================================
                 */
                if ($approvalMode) {

                    $action = $this->input('action');

                    /*
                     * Determine the correct end date for validation.
                     *
                     * update:
                     *     Existing requested end date.
                     *
                     * approve / update_and_approve:
                     *     Submitted approved end date.
                     *
                     * reject:
                     *     Existing requested end date.
                     */
                    if (
                        in_array(
                            $action,
                            [
                                'approve',
                                'update_and_approve',
                            ],
                            true
                        )
                    ) {
                        $effectiveEndDate = $this->input(
                            'approved_to_date'
                        );
                    } else {
                        $effectiveEndDate =
                            $leaveRequest->requested_to_date;
                    }

                    $this->validateReturnToWorkDate(
                        $validator,
                        $effectiveEndDate
                    );

                    /*
                     * Attachment requirement
                     */
                    $this->validateAttachmentRequirement(
                        $validator,
                        $leaveRequest
                    );

                    /*
                     * Multi-day half-day leave is allowed.
                     */
                    return;
                }

                /*
                 * ==================================================
                 * FALLBACK
                 * ==================================================
                 */
                $this->validateReturnToWorkDate(
                    $validator,
                    $this->input('requested_to_date')
                );

                $this->validateAttachmentRequirement(
                    $validator,
                    $leaveRequest
                );
            }
        );
    }

    /**
     * Validate the expected return-to-work date against
     * the applicable leave end date.
     *
     * A blank value is allowed.
     */
    protected function validateReturnToWorkDate(
        Validator $validator,
        mixed $leaveEndDate
    ): void {
        /*
         * Skip when the field is absent or intentionally cleared.
         */
        if (!$this->filled('return_to_work_date')) {
            return;
        }

        /*
         * Skip comparison if the return date itself failed
         * the basic date validation rule.
         */
        if (
            $validator->errors()->has('return_to_work_date')
        ) {
            return;
        }

        if (!$leaveEndDate) {
            return;
        }

        try {
            $returnDate = Carbon::parse(
                $this->input('return_to_work_date')
            )->startOfDay();

            $leaveEndDate = Carbon::parse(
                $leaveEndDate
            )->startOfDay();
        } catch (\Throwable $exception) {
            return;
        }

        if ($returnDate->lt($leaveEndDate)) {
            $validator->errors()->add(
                'return_to_work_date',
                'The expected return-to-work date must be on or after the leave end date.'
            );
        }
    }

    /**
     * Format a date value as Y-m-d.
     */
    protected function formatDate(mixed $date): ?string
    {
        if (!$date) {
            return null;
        }

        if (
            $date instanceof Carbon
            || $date instanceof \Carbon\CarbonImmutable
        ) {
            return $date->format('Y-m-d');
        }

        return Carbon::parse($date)->format('Y-m-d');
    }

    /**
     * Validate whether an attachment is required.
     *
     * This method correctly handles restricted edit where
     * leave_type_id is not submitted.
     */
    protected function validateAttachmentRequirement(
        Validator $validator,
        ?LeaveRequest $leaveRequest
    ): void {
        /*
         * If leave_type_id is submitted, use it.
         *
         * Otherwise use the existing leave request's leave type.
         */
        $leaveTypeId = $this->input('leave_type_id');

        if (!$leaveTypeId && $leaveRequest) {
            $leaveTypeId = $leaveRequest->leave_type_id;
        }

        if (!$leaveTypeId) {
            return;
        }

        $leaveType = LeaveType::find($leaveTypeId);

        if (!$leaveType) {
            return;
        }

        /*
         * This leave type does not require an attachment.
         */
        if (!$leaveType->is_file_upload_required) {
            return;
        }

        /*
         * A new uploaded attachment satisfies the requirement.
         */
        if (
            $this->hasFile('attachment')
            && $this->file('attachment')->isValid()
        ) {
            return;
        }

        /*
         * An existing attachment satisfies the requirement.
         *
         * LeaveRequest uses a polymorphic attachments relationship.
         *
         * Only active attachments are considered.
         */
        if (
            $leaveRequest
            && $leaveRequest->attachments()
                ->where('is_active', 1)
                ->exists()
        ) {
            return;
        }

        /*
         * Otherwise attachment is required.
         */
        $validator->errors()->add(
            'attachment',
            'A supporting document is required for this leave type.'
        );
    }
}

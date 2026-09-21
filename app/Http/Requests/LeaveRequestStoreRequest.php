<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LeaveRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'in:full_day,half_day',
            ],

            'leave_type_id' => [
                'required',
                'exists:leave_types,id',
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

            /*
             * Required only for half-day leave.
             *
             * Multi-day half-day leave is allowed.
             */
            'half_day_type' => [
                'nullable',
                'required_if:type,half_day',
                Rule::in([
                    'first_half',
                    'second_half',
                ]),
            ],

            'reason' => [
                'required',
                'string',
                'max:2000',
            ],

            /*
             * Attachment is optional at the base validation level.
             *
             * The conditional requirement for leave types such as
             * Sick Leave is handled in withValidator().
             */
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {
                /*
                 * ------------------------------------------------------
                 * Attachment validation
                 * ------------------------------------------------------
                 *
                 * For normal Leave Management applications:
                 * - Attachment is required when the leave type requires it.
                 *
                 * For Attendance Sheet applications:
                 * - Attachment is not required initially.
                 * - The employee can provide the supporting document later.
                 */

                $leaveTypeId = $this->input('leave_type_id');

                if (!$leaveTypeId) {
                    return;
                }

                $leaveType = LeaveType::find($leaveTypeId);

                if (!$leaveType) {
                    return;
                }

                /*
                 * Check whether this request was created from
                 * the Attendance Sheet.
                 */
                $createdFromAttendance = $this->boolean(
                    'created_from_attendance'
                );

                /*
                 * Skip the attachment requirement when a manager
                 * or reporting person marks the leave from Attendance.
                 */
                if ($createdFromAttendance) {
                    return;
                }

                /*
                 * For normal leave applications, require an attachment
                 * when the selected leave type requires supporting documents.
                 */
                if (
                    $leaveType->is_file_upload_required
                    && !$this->hasFile('attachment')
                ) {
                    $validator->errors()->add(
                        'attachment',
                        'A supporting document is required for this leave type.'
                    );
                }
            }
        );
    }
}

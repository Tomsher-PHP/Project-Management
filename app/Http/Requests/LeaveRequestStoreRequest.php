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
             * The selected period applies to every date
             * in the requested date range.
             *
             * Example:
             * 04 Sep - 09 Sep + Morning
             * = 0.5 day for each date.
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
            function (Validator $validator) {

                /*
                 * ------------------------------------------------------
                 * DO NOT restrict half-day to a single date.
                 * ------------------------------------------------------
                 *
                 * Multi-day half-day is allowed.
                 *
                 * Example:
                 *
                 * 04 Sep - 09 Sep
                 * Morning
                 * = 6 × 0.5
                 * = 3.00 days
                 */

                /*
                 * ------------------------------------------------------
                 * Attachment validation
                 * ------------------------------------------------------
                 */
                if ($this->leave_type_id) {

                    $leaveType = LeaveType::find(
                        $this->leave_type_id
                    );

                    if (
                        $leaveType
                        && $leaveType->is_file_upload_required
                        && !$this->hasFile('attachment')
                    ) {
                        $validator->errors()->add(
                            'attachment',
                            'A supporting document is required for this leave type.'
                        );
                    }
                }
            }
        );
    }
}

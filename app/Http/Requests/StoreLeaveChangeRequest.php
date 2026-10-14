<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->can('leave_change_request.create');
    }

    public function rules(): array
    {
        return [
            'new_leave_type_id' => [
                'required',
                'exists:leave_types,id',
            ],

            'new_from_date' => [
                'required',
                'date',
            ],

            'new_to_date' => [
                'required',
                'date',
                'after_or_equal:new_from_date',
            ],

            'new_duration' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'new_day_type' => [
                'required',
                'in:full_day,half_day',
            ],

            'new_half_day_type' => [
                'nullable',
                'required_if:new_day_type,half_day',
                'in:first,second',
            ],

            'reason' => [
                'required',
                'string',
                'max:2000',
            ],

            /*
             * Use your existing attachment validation rules/service
             * here if an attachment is required for the selected
             * leave type.
             */
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'new_leave_type_id.required' =>
                'Please select the leave type.',

            'new_from_date.required' =>
                'Please select the start date.',

            'new_to_date.required' =>
                'Please select the end date.',

            'new_to_date.after_or_equal' =>
                'The end date must be on or after the start date.',

            'new_duration.required' =>
                'Please enter the leave duration.',

            'new_duration.gt' =>
                'Leave duration must be greater than zero.',

            'new_day_type.required' =>
                'Please select the day type.',

            'new_half_day_type.required_if' =>
                'Please select which half of the day.',

            'reason.required' =>
                'Please provide a reason for the leave change request.',
        ];
    }
}

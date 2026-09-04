<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leaveTypeId = $this->route('leave_type');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('leave_types', 'name')
                    ->ignore($leaveTypeId),
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('leave_types', 'code')
                    ->ignore($leaveTypeId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'is_file_upload_required' => [
                'nullable',
                'boolean',
            ],

            'is_paid' => [
                'nullable',
                'boolean',
            ],

            'status' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the leave type name.',
            'name.unique' => 'This leave type already exists.',

            'code.required' => 'Please enter a leave type code.',
            'code.unique' => 'This leave type code already exists.',
            'code.alpha_dash' => 'The code may only contain letters, numbers, dashes and underscores.',
        ];
    }
}
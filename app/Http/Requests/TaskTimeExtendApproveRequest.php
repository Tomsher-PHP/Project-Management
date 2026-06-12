<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskTimeExtendApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_estimated_time_minutes' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'new_estimated_time_minutes.required' => 'Please enter a valid estimated time.',
            'new_estimated_time_minutes.integer' => 'Estimated time must be an integer.',
            'new_estimated_time_minutes.min' => 'Estimated time must be at least 1 minute.',
        ];
    }
}

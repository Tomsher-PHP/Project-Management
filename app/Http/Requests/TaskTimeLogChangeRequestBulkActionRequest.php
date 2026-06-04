<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskTimeLogChangeRequestBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'change_request_ids' => ['required', 'array', 'min:1'],
            'change_request_ids.*' => ['integer', 'distinct', 'exists:task_time_log_change_requests,id'],
            'reason' => [
                $this->route('action') === 'reject' ? 'required' : 'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'change_request_ids.required' => 'Please select at least one time log change request.',
            'change_request_ids.min' => 'Please select at least one time log change request.',
            'change_request_ids.*.distinct' => 'Duplicate time log change requests cannot be processed.',
            'change_request_ids.*.exists' => 'One or more selected time log change requests no longer exist.',
            'reason.required' => 'Please enter a rejection reason.',
            'reason.max' => 'The rejection reason cannot be longer than 2000 characters.',
        ];
    }
}

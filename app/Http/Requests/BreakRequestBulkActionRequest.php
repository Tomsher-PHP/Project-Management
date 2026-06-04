<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BreakRequestBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'break_request_ids' => ['required', 'array', 'min:1'],
            'break_request_ids.*' => ['integer', 'distinct', 'exists:break_work_requests,id'],
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
            'break_request_ids.required' => 'Please select at least one break work request.',
            'break_request_ids.min' => 'Please select at least one break work request.',
            'break_request_ids.*.distinct' => 'Duplicate break work requests cannot be processed.',
            'break_request_ids.*.exists' => 'One or more selected break work requests no longer exist.',
            'reason.required' => 'Please enter a rejection reason.',
            'reason.max' => 'The rejection reason cannot be longer than 2000 characters.',
        ];
    }
}

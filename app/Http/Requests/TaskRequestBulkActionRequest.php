<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequestBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'distinct', 'exists:tasks,id'],
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
            'task_ids.required' => 'Please select at least one task request.',
            'task_ids.min' => 'Please select at least one task request.',
            'task_ids.*.distinct' => 'Duplicate task requests cannot be processed.',
            'task_ids.*.exists' => 'One or more selected task requests no longer exist.',
            'reason.required' => 'Please enter a rejection reason.',
            'reason.max' => 'The rejection reason cannot be longer than 2000 characters.',
        ];
    }
}

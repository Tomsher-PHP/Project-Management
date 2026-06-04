<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BreakRequestActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            'reason.required' => 'Please enter a rejection reason.',
            'reason.max' => 'The rejection reason cannot be longer than 2000 characters.',
        ];
    }
}

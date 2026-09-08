<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppraisalAddReviewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reviewer_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'reviewer_user_id.required' => 'Select a reviewer.',
            'reviewer_user_id.exists' => 'The selected reviewer user is not available.',
        ];
    }
}

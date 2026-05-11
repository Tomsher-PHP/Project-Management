<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => auth()->user()->is_super_admin ? 'nullable' : 'required',
            'new_password' => 'required|min:6|confirmed',
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Please enter your current password.',

            'new_password.required' => 'Please enter a new password.',
            'new_password.min' => 'New password must be at least 6 characters.',
            'new_password.confirmed' => 'Passwords do not match.',
        ];
    }
}

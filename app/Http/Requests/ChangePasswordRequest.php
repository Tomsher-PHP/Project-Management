<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
            'new_password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Please enter your current password.',

            'new_password.required' => 'Please enter a new password.',
            'new_password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('new_password')) {
                $messages = $validator->errors()->get('new_password');
                $confirmMessages = array_filter($messages, fn($msg) => str_contains(strtolower($msg), 'confirm'));
                if (!empty($confirmMessages)) {
                    foreach ($confirmMessages as $msg) {
                        $validator->errors()->add('new_password_confirmation', $msg);
                    }
                    $remaining = array_values(array_filter($messages, fn($msg) => !str_contains(strtolower($msg), 'confirm')));
                    $validator->errors()->forget('new_password');
                    foreach ($remaining as $msg) {
                        $validator->errors()->add('new_password', $msg);
                    }
                }
            }
        });
    }
}

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
}

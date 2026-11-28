<?php

namespace App\Http\Requests\CustomerPortal\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
        ];
    }

    public function normalizedUsername(): string
    {
        return strtolower(trim((string) $this->input('username', '')));
    }
}

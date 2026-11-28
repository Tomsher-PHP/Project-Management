<?php

namespace App\Http\Requests\CustomerPortal\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    protected $redirectRoute = 'customer.login';

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
            'password' => ['required', 'string'],
        ];
    }

    public function normalizedUsername(): string
    {
        return strtolower(trim((string) $this->input('username', '')));
    }

    public function submittedPassword(): string
    {
        return (string) $this->input('password', '');
    }
}

<?php

namespace App\Http\Requests\CustomerPortal\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    protected $redirectRoute = 'customer.password.reset';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function submittedPassword(): string
    {
        return (string) $this->input('password', '');
    }
}

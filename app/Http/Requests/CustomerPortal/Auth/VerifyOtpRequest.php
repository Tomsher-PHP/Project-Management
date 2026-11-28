<?php

namespace App\Http\Requests\CustomerPortal\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    protected $redirectRoute = 'customer.password.otp';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $otpLength = (int) config('customer_portal.password_reset.otp_length', 6);

        return [
            'otp' => ['required', 'string', 'regex:/^\d{' . $otpLength . '}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $otpLength = (int) config('customer_portal.password_reset.otp_length', 6);

        return [
            'otp.required' => 'Please enter the verification code.',
            'otp.regex' => "The verification code must be a {$otpLength}-digit number.",
        ];
    }

    public function submittedOtp(): string
    {
        return (string) $this->input('otp', '');
    }
}

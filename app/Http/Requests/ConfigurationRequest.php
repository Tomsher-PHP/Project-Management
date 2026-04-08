<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'email_suffix' => [
                'nullable',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $suffix = (string) $value;

                    if (!str_starts_with($suffix, '@') || !filter_var('user' . $suffix, FILTER_VALIDATE_EMAIL)) {
                        $fail('The email suffix must be a valid email suffix like @gmail.com.');
                    }
                },
            ],
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:500',

            'timezone' => 'required|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:20',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'website' => filled($this->input('website')) ? trim((string) $this->input('website')) : null,
            'email_suffix' => filled($this->input('email_suffix')) ? strtolower(trim((string) $this->input('email_suffix'))) : null,
        ]);
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServerHostingNotificationSettingRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'is_enabled' => ['nullable', 'boolean'],
            'days_before' => ['required', 'integer', 'min:0'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'emails' => ['nullable', 'array'],
            'emails.*' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * Custom validation logic.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $emails = $this->input('emails', []);
            if (!is_array($emails)) {
                return;
            }

            $seen = [];
            foreach ($emails as $index => $email) {
                $trimmed = strtolower(trim((string) $email));
                if ($trimmed === '') {
                    continue;
                }
                if (in_array($trimmed, $seen, true)) {
                    $validator->errors()->add("emails.{$index}", "The email address '{$email}' is duplicated.");
                } else {
                    $seen[] = $trimmed;
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $emails = $this->input('emails', []);

        if (is_string($emails)) {
            $emails = array_filter(array_map('trim', explode(',', $emails)));
        }

        if (is_array($emails)) {
            $emails = array_values(array_filter(array_map(function ($email) {
                return is_string($email) ? trim($email) : $email;
            }, $emails), fn($email) => $email !== '' && $email !== null));
        }

        $this->merge([
            'is_enabled' => filter_var($this->input('is_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'emails' => $emails,
        ]);
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'emails.*' => 'email address',
            'user_ids.*' => 'user recipient',
        ];
    }
}

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
            'days_before' => ['required', 'array', 'min:1', 'max:6'],
            'days_before.*' => ['required', 'integer', 'min:0'],
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
            // Check for duplicate days_before intervals
            $days = $this->input('days_before', []);
            if (is_array($days)) {
                $seenDays = [];
                foreach ($days as $index => $day) {
                    if ($day === null || $day === '') {
                        continue;
                    }
                    $intDay = (int) $day;
                    if (in_array($intDay, $seenDays, true)) {
                        $validator->errors()->add("days_before.{$index}", "The reminder interval '{$day}' day(s) is duplicated.");
                    } else {
                        $seenDays[] = $intDay;
                    }
                }
            }

            // Check for duplicate emails
            $emails = $this->input('emails', []);
            if (is_array($emails)) {
                $seenEmails = [];
                foreach ($emails as $index => $email) {
                    $trimmed = strtolower(trim((string) $email));
                    if ($trimmed === '') {
                        continue;
                    }
                    if (in_array($trimmed, $seenEmails, true)) {
                        $validator->errors()->add("emails.{$index}", "The email address '{$email}' is duplicated.");
                    } else {
                        $seenEmails[] = $trimmed;
                    }
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // 1. Process emails (multiline textarea support or array)
        $rawEmails = $this->input('emails', []);
        $parsedEmails = [];

        if (is_string($rawEmails)) {
            // Split by newline and comma
            $lines = preg_split('/[\r\n,]+/', $rawEmails);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $parsedEmails[] = $trimmed;
                }
            }
        } elseif (is_array($rawEmails)) {
            foreach ($rawEmails as $item) {
                if (is_string($item)) {
                    $lines = preg_split('/[\r\n,]+/', $item);
                    foreach ($lines as $line) {
                        $trimmed = trim($line);
                        if ($trimmed !== '') {
                            $parsedEmails[] = $trimmed;
                        }
                    }
                }
            }
        }

        // 2. Process days_before (ensure array of clean values without blank empty string entries from UI)
        $rawDays = $this->input('days_before', []);
        $parsedDays = [];
        if (is_array($rawDays)) {
            foreach ($rawDays as $d) {
                if ($d !== null && trim((string) $d) !== '') {
                    $parsedDays[] = is_numeric($d) ? (int) $d : $d;
                }
            }
        } elseif (is_numeric($rawDays)) {
            $parsedDays[] = (int) $rawDays;
        }

        $this->merge([
            'is_enabled' => filter_var($this->input('is_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'emails' => $parsedEmails,
            'days_before' => $parsedDays,
        ]);
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'days_before.required' => 'At least one reminder interval is required.',
            'days_before.min' => 'At least one reminder interval is required.',
            'days_before.max' => 'You can add a maximum of 5 additional reminder intervals (6 total).',
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'days_before' => 'reminder intervals',
            'days_before.*' => 'reminder interval',
            'emails' => 'additional email recipients',
            'emails.*' => 'email address',
            'user_ids.*' => 'user recipient',
        ];
    }
}

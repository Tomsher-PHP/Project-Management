<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectReportRecipientsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('report_settings.manage') ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('user_ids') && is_array($this->input('user_ids'))) {
            $this->merge([
                'user_ids' => array_values(array_unique(array_filter($this->input('user_ids')))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
            'external_recipients' => ['nullable', 'array'],
            'external_recipients.*.email' => [
                'required',
                'string',
                'email:rfc',
                'max:254',
            ],
            'external_recipients.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Configure validator instance to prevent duplicate recipient emails.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $externalRecipients = (array) $this->input('external_recipients', []);
            $seenEmails = [];

            foreach ($externalRecipients as $index => $item) {
                $email = mb_strtolower(trim($item['email'] ?? ''));
                if ($email === '') {
                    continue;
                }

                if (in_array($email, $seenEmails, true)) {
                    $validator->errors()->add(
                        "external_recipients.{$index}.email",
                        "The email '{$email}' is duplicated in external recipients."
                    );
                }
                $seenEmails[] = $email;
            }

            $userIds = (array) $this->input('user_ids', []);
            if (!empty($userIds) && !empty($seenEmails)) {
                $internalEmails = User::whereIn('id', $userIds)
                    ->pluck('email')
                    ->map(fn ($e) => mb_strtolower(trim($e)))
                    ->all();

                foreach ($externalRecipients as $index => $item) {
                    $email = mb_strtolower(trim($item['email'] ?? ''));
                    if (in_array($email, $internalEmails, true)) {
                        $validator->errors()->add(
                            "external_recipients.{$index}.email",
                            "The email '{$email}' belongs to a selected internal employee."
                        );
                    }
                }
            }
        });
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.*.exists' => 'One or more selected internal employees are invalid or inactive.',
            'external_recipients.*.email.required' => 'Email address is required for all external recipients.',
            'external_recipients.*.email.email' => 'Please provide a valid email address.',
            'external_recipients.*.email.max' => 'Email address cannot exceed 254 characters.',
        ];
    }
}

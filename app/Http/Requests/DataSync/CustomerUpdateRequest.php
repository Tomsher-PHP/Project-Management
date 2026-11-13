<?php

namespace App\Http\Requests\DataSync;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class CustomerUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('external_id') && $this->input('external_id') !== null && ! is_array($this->input('external_id'))) {
            $this->merge(['external_id' => (string) $this->input('external_id')]);
        }

        if ($this->has('country_code') && $this->input('country_code') !== null && ! is_array($this->input('country_code'))) {
            $this->merge(['country_code' => strtoupper(trim((string) $this->input('country_code')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'industry_code' => ['sometimes', 'nullable', 'digits:6'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'country_code' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Za-z]{2,3}$/', 'max:3'],
            'emirate' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_address' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'sales_person_email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'external_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contacts' => ['sometimes', 'nullable', 'array'],
            'contacts.*.id' => ['sometimes', 'nullable', 'integer'],
            'contacts.*.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:150'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:255'],
            'contacts.*.whatsapp' => ['nullable', 'string', 'max:255'],
            'contacts.*.landline' => ['nullable', 'string', 'max:255'],
            'contacts.*.designation' => ['nullable', 'string', 'max:255'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $contacts = $this->input('contacts');
            if (is_array($contacts)) {
                $primaryCount = 0;
                foreach ($contacts as $index => $contact) {
                    if (! is_array($contact)) {
                        continue;
                    }

                    $hasId = isset($contact['id']) && $contact['id'] !== null && $contact['id'] !== '';
                    if (! $hasId && (! isset($contact['name']) || trim((string) $contact['name']) === '')) {
                        $validator->errors()->add("contacts.{$index}.name", 'The contact name is required for new contacts.');
                    }

                    if (! empty($contact['is_primary'])) {
                        $primaryCount++;
                    }
                }

                if ($primaryCount > 1) {
                    $validator->errors()->add('contacts', 'Multiple primary contacts are not allowed.');
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}


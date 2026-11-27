<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $contacts = $this->input('contacts', []);

        if (is_array($contacts)) {
            $contacts = array_map(function ($contact) {
                if (! is_array($contact)) {
                    return $contact;
                }

                $contact['login_enabled'] = in_array(
                    $contact['login_enabled'] ?? false,
                    [true, 1, '1', 'true', 'on'],
                    true,
                );

                return $contact;
            }, $contacts);
        }

        $this->merge([
            'primary_login_enabled' => $this->boolean('primary_login_enabled'),
            'contacts' => $contacts,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = $customer?->id;
        $primaryContactId = $customer?->primaryContact?->id;

        $rules = [
            // Customer
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'industry_id' => ['nullable', 'exists:industries,id'],
            'website' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $url = trim((string) $value);
                    if ($url === '') {
                        return;
                    }

                    $candidate = preg_match('/^https?:\/\//i', $url)
                        ? $url
                        : 'https://' . ltrim($url, '/');

                    if (! filter_var($candidate, FILTER_VALIDATE_URL)) {
                        $fail('Please enter a valid website URL.');

                        return;
                    }

                    $host = parse_url($candidate, PHP_URL_HOST);
                    if (! is_string($host) || ! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/i', $host)) {
                        $fail('Please enter a valid website URL.');
                    }
                },
            ],
            'registered_country_id' => ['nullable', 'exists:countries,id'],
            'emirate' => ['nullable', 'string', 'max:255'],
            'google_map_link' => ['nullable', 'url', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:65535'],
            'sales_person_id' => ['required', 'exists:users,id'],
            'new_to_company' => ['sometimes', 'boolean'],

            // Primary Contact
            'primary_name' => ['required', 'string', 'max:255'],
            'primary_email' => [
                'bail',
                Rule::requiredIf(fn () => $this->boolean('primary_login_enabled')),
                'nullable',
                'email',
                'max:150',
                Rule::unique('customer_contacts', 'email')->ignore($primaryContactId),
            ],
            'primary_landline' => ['nullable', 'string', 'max:255'],
            'primary_mobile' => ['nullable', 'string', 'max:255'],
            'primary_whatsapp' => ['nullable', 'string', 'max:255'],
            'primary_designation' => ['nullable', 'string', 'max:255'],
            'primary_login_enabled' => ['required', 'boolean'],
            'primary_password' => [
                'nullable',
                'string',
                Password::min(8)->letters()->numbers(),
            ],

            // Extra Contacts
            'contacts' => ['nullable', 'array'],
            'contacts.*.id' => [
                'nullable',
                'integer',
                Rule::exists('customer_contacts', 'id')->where(function ($query) use ($customerId) {
                    $query->where('is_primary', false);

                    if ($customerId) {
                        $query->where('customer_id', $customerId);
                    }
                }),
            ],
            'contacts.*.name' => ['required', 'string', 'max:255'],
            'contacts.*.landline' => ['nullable', 'string', 'max:255'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:255'],
            'contacts.*.whatsapp' => ['nullable', 'string', 'max:255'],
            'contacts.*.designation' => ['nullable', 'string', 'max:255'],
            'contacts.*.is_primary' => ['sometimes', 'boolean'],
            'contacts.*.is_active' => ['sometimes', 'boolean'],
            'contacts.*.login_enabled' => ['required', 'boolean'],
        ];

        foreach ($this->input('contacts', []) as $index => $contact) {
            if (! is_array($contact)) {
                continue;
            }

            $contactId = $contact['id'] ?? null;
            $loginEnabled = (bool) ($contact['login_enabled'] ?? false);

            $rules["contacts.{$index}.email"] = [
                'bail',
                Rule::requiredIf($loginEnabled),
                'nullable',
                'email',
                'max:150',
                Rule::unique('customer_contacts', 'email')->ignore($contactId),
            ];

            $rules["contacts.{$index}.password"] = [
                'nullable',
                'string',
                Password::min(8)->letters()->numbers(),
            ];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasContactMethod([
                $this->input('primary_email'),
                $this->input('primary_mobile'),
                $this->input('primary_whatsapp'),
                $this->input('primary_landline'),
            ])) {
                $validator->errors()->add(
                    'primary_email',
                    'Please provide at least one contact method (Email, Mobile, WhatsApp, or Landline).',
                );
            }

            $seenEmails = [];
            $primaryEmail = $this->normalizedEmail($this->input('primary_email'));

            if ($primaryEmail) {
                $seenEmails[$primaryEmail] = 'primary_email';
            }

            foreach ($this->input('contacts', []) as $index => $contact) {
                if (! is_array($contact)) {
                    continue;
                }

                if (! $this->hasContactMethod([
                    $contact['email'] ?? null,
                    $contact['mobile'] ?? null,
                    $contact['whatsapp'] ?? null,
                    $contact['landline'] ?? null,
                ])) {
                    $validator->errors()->add(
                        "contacts.{$index}.email",
                        'Please provide at least one contact method (Email, Mobile, WhatsApp, or Landline).',
                    );
                }

                $email = $this->normalizedEmail($contact['email'] ?? null);

                if (! $email) {
                    continue;
                }

                if (isset($seenEmails[$email])) {
                    $validator->errors()->add(
                        "contacts.{$index}.email",
                        'This contact email is already in use.',
                    );
                } else {
                    $seenEmails[$email] = "contacts.{$index}.email";
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Company name is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This customer email is already in use.',
            'industry_id.exists' => 'Selected industry is invalid.',
            'registered_country_id.exists' => 'Selected country is invalid.',
            'website.url' => 'Please enter a valid website URL.',
            'google_map_link.url' => 'Please enter a valid Google Map link.',
            'sales_person_id.required' => 'Sales person is required.',
            'sales_person_id.exists' => 'The selected sales person is invalid.',

            'primary_name.required' => 'Contact name is required.',
            'primary_email.required' => 'Email is required when login access is enabled.',
            'primary_email.email' => 'Please enter a valid email address for contact.',
            'primary_email.unique' => 'This contact email is already in use.',
            'primary_login_enabled.boolean' => 'The login access selection is invalid.',
            'primary_password.min' => 'Password must be at least 8 characters.',
            'primary_password.letters' => 'Password must contain at least one letter.',
            'primary_password.numbers' => 'Password must contain at least one number.',

            'contacts.array' => 'Contacts must be an array.',
            'contacts.*.id.exists' => 'The selected contact is invalid.',
            'contacts.*.name.required' => 'Contact name is required.',
            'contacts.*.email.required' => 'Email is required when login access is enabled.',
            'contacts.*.email.email' => 'Please enter a valid email address for contact.',
            'contacts.*.email.unique' => 'This contact email is already in use.',
            'contacts.*.is_primary.boolean' => 'Is primary must be true or false.',
            'contacts.*.is_active.boolean' => 'Is active must be true or false.',
            'contacts.*.login_enabled.boolean' => 'The login access selection is invalid.',
            'contacts.*.password.min' => 'Password must be at least 8 characters.',
            'contacts.*.password.letters' => 'Password must contain at least one letter.',
            'contacts.*.password.numbers' => 'Password must contain at least one number.',
        ];
    }

    public function attributes(): array
    {
        return [
            'primary_password' => 'portal password',
            'contacts.*.email' => 'contact email',
            'contacts.*.password' => 'contact password',
        ];
    }

    private function hasContactMethod(array $values): bool
    {
        return collect($values)->contains(fn ($value) => filled($value));
    }

    private function normalizedEmail(mixed $email): ?string
    {
        if (blank($email)) {
            return null;
        }

        return mb_strtolower(trim((string) $email));
    }
}

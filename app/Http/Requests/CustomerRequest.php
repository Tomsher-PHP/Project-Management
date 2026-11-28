<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = $customer?->id;

        return [
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
            'primary_email' => ['nullable', 'email', 'max:150'],
            'primary_landline' => ['nullable', 'string', 'max:255'],
            'primary_mobile' => ['nullable', 'string', 'max:255'],
            'primary_whatsapp' => ['nullable', 'string', 'max:255'],
            'primary_designation' => ['nullable', 'string', 'max:255'],

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
            'contacts.*.email' => ['nullable', 'email', 'max:150'],
            'contacts.*.landline' => ['nullable', 'string', 'max:255'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:255'],
            'contacts.*.whatsapp' => ['nullable', 'string', 'max:255'],
            'contacts.*.designation' => ['nullable', 'string', 'max:255'],
            'contacts.*.is_primary' => ['sometimes', 'boolean'],
            'contacts.*.is_active' => ['sometimes', 'boolean'],
        ];
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
            'primary_email.email' => 'Please enter a valid email address for contact.',

            'contacts.array' => 'Contacts must be an array.',
            'contacts.*.id.exists' => 'The selected contact is invalid.',
            'contacts.*.name.required' => 'Contact name is required.',
            'contacts.*.email.email' => 'Please enter a valid email address for contact.',
            'contacts.*.is_primary.boolean' => 'Is primary must be true or false.',
            'contacts.*.is_active.boolean' => 'Is active must be true or false.',
        ];
    }

    public function attributes(): array
    {
        return [
            'contacts.*.email' => 'contact email',
        ];
    }

    private function hasContactMethod(array $values): bool
    {
        return collect($values)->contains(fn ($value) => filled($value));
    }
}

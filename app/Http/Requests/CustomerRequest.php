<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
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
            'website' => ['nullable', 'url', 'max:255'],
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

            // Customer Portal Login
            'primary_username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('customer_contacts', 'username')->ignore($primaryContactId),
            ],
            'primary_password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            // Additional Contacts
            'contacts' => ['nullable', 'array'],
            'contacts.*.id' => [
                'nullable',
                'integer',
                'exists:customer_contacts,id',
            ],
            'contacts.*.name' => ['required', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:150'],
            'contacts.*.landline' => ['nullable', 'string', 'max:255'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:255'],
            'contacts.*.whatsapp' => ['nullable', 'string', 'max:255'],
            'contacts.*.designation' => ['nullable', 'string', 'max:255'],
            'contacts.*.is_primary' => ['sometimes', 'boolean'],
            'contacts.*.is_active' => ['sometimes', 'boolean'],

            // Login Access
            'contacts.*.needs_login' => ['sometimes', 'boolean'],
        ];

        /*
         * Apply login validation to each individual contact.
         */
        foreach ($this->input('contacts', []) as $index => $contact) {
            if (!is_array($contact)) {
                continue;
            }

            $needsLogin = in_array(
                $contact['needs_login'] ?? false,
                [true, 1, '1'],
                true
            );

            if ($needsLogin) {
                $rules["contacts.{$index}.email"] = [
                    'bail',
                    'required',
                    'email',
                    'max:150',
                ];

                $rules["contacts.{$index}.password"] = [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ];

                $rules["contacts.{$index}.password_confirmation"] = [
                    'required',
                    'string',
                ];
            }
        }

        return $rules;
    }

    /**
     * Configure additional validation.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $customer = $this->route('customer');

            // At least one primary contact method is required.
            if (
                empty($this->input('primary_email')) &&
                empty($this->input('primary_mobile')) &&
                empty($this->input('primary_whatsapp')) &&
                empty($this->input('primary_landline'))
            ) {
                $validator->errors()->add(
                    'primary_email',
                    'At least one required (Email, Mobile, WhatsApp, Landline).'
                );
            }

            // Customer Portal Login
            if (
                filled($this->input('primary_password')) &&
                blank($this->input('primary_username'))
            ) {
                $validator->errors()->add(
                    'primary_username',
                    'Username is required when setting a password.'
                );
            }

            if (
                filled($this->input('primary_username')) &&
                blank($this->input('primary_password')) &&
                (!$customer || blank($customer->primaryContact?->password))
            ) {
                $validator->errors()->add(
                    'primary_password',
                    'Password is required when creating customer portal credentials.'
                );
            }
        });
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            // Customer
            'name.required' => 'Company name is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This customer email is already in use.',
            'industry_id.exists' => 'Selected industry is invalid.',
            'registered_country_id.exists' => 'Selected country is invalid.',
            'website.url' => 'Please enter a valid website URL.',
            'google_map_link.url' => 'Please enter a valid Google Map link.',
            'sales_person_id.required' => 'Sales person is required.',
            'sales_person_id.exists' => 'The selected sales person is invalid.',

            // Primary Contact
            'primary_name.required' => 'Contact name is required.',
            'primary_email.email' => 'Please enter a valid email address for contact.',
            'primary_landline.string' => 'Contact landline must be a string.',
            'primary_mobile.string' => 'Contact mobile must be a string.',
            'primary_whatsapp.string' => 'Contact WhatsApp must be a string.',
            'primary_designation.string' => 'Contact designation must be a string.',

            // Customer Portal Login
            'primary_username.unique' => 'This customer portal username is already in use.',
            'primary_password.min' => 'Portal password must be at least 8 characters.',
            'primary_password.confirmed' => 'Portal password confirmation does not match.',

            // Additional Contacts
            'contacts.array' => 'Contacts must be an array.',
            'contacts.*.id.exists' => 'The selected contact is invalid.',
            'contacts.*.name.required' => 'Contact name is required.',
            'contacts.*.email.email' => 'Please enter a valid email address for contact.',
            'contacts.*.is_primary.boolean' => 'Is primary must be true or false.',
            'contacts.*.is_active.boolean' => 'Is active must be true or false.',
            'contacts.*.needs_login.boolean' => 'The login access selection is invalid.',

            // Login Access
            'contacts.*.email.required' => 'Email is required when login access is enabled.',
            'contacts.*.password.required' => 'Password is required when login access is enabled.',
            'contacts.*.password.min' => 'Password must be at least 8 characters.',
            'contacts.*.password.confirmed' => 'Password confirmation does not match.',
            'contacts.*.password_confirmation.required' => 'Please confirm the password.',
        ];
    }

    /**
     * Custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'primary_username' => 'portal username',
            'primary_password' => 'portal password',
            'primary_password_confirmation' => 'portal password confirmation',
            'contacts.*.email' => 'contact email',
            'contacts.*.password' => 'contact password',
            'contacts.*.password_confirmation' => 'password confirmation',
        ];
    }
}


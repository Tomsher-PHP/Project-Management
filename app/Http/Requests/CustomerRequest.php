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

        $customerId = $this->route('customer')?->id;

        return [
            // Customer
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customerId)],
            'industry_id' => 'nullable|exists:industries,id',
            'website' => 'nullable|url|max:255',
            'registered_country_id' => 'nullable|exists:countries,id',
            'emirate' => 'nullable|string|max:255',
            'google_map_link' => 'nullable|url|max:255',
            'company_address' => 'nullable|string|max:65535',
            'sales_person' => 'required|string|max:255',
            'new_to_company' => 'boolean',

            // Primary Contact (IMPORTANT)
            'primary_name' => 'required|string|max:255',
            'primary_email' => 'nullable|email|max:150',
            'primary_landline' => 'nullable|string|max:255',
            'primary_mobile' => 'nullable|string|max:255',
            'primary_whatsapp' => 'nullable|string|max:255',
            'primary_designation' => 'nullable|string|max:255',

            // Additional Contacts
            'contacts' => 'nullable|array',
            'contacts.*.id' => 'nullable|exists:customer_contacts,id',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.email' => 'nullable|email|max:150',
            'contacts.*.landline' => 'nullable|string|max:255',
            'contacts.*.mobile' => 'nullable|string|max:255',
            'contacts.*.whatsapp' => 'nullable|string|max:255',
            'contacts.*.designation' => 'nullable|string|max:255',
            'contacts.*.is_primary' => 'boolean',
            'contacts.*.is_active' => 'boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (
                empty($this->primary_email) &&
                empty($this->primary_mobile) &&
                empty($this->primary_whatsapp) &&
                empty($this->primary_landline)
            ) {
                $validator->errors()->add(
                    'primary_email',
                    'At least one required (Email, Mobile, WhatsApp, Landline).'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            // Customer
            'name.required' => 'Company name is required.',
            'email.email' => 'Please enter a valid email address.',
            'industry_id.exists' => 'Selected industry is invalid.',
            'registered_country_id.exists' => 'Selected country is invalid.',
            'website.url' => 'Please enter a valid website URL.',
            'google_map_link.url' => 'Please enter a valid Google Map link.',

            // Primary Contact
            'primary_name.required' => 'Contact name is required.',
            'primary_email.email' => 'Please enter a valid email address for contact.',
            'primary_landline.string' => 'Contact landline must be a string.',
            'primary_mobile.string' => 'Contact mobile must be a string.',
            'primary_whatsapp.string' => 'Contact whatsapp must be a string.',
            'primary_designation.string' => 'Contact designation must be a string.',

            // Additional Contacts
            'contacts.array' => 'Contacts must be an array.',
            'contacts.*.name.required' => 'Contact name is required.',
            'contacts.*.email.email' => 'Please enter a valid email address for contact.',
            'contacts.*.is_primary.boolean' => 'Is primary must be true or false.',
            'contacts.*.is_active.boolean' => 'Is active must be true or false.',
        ];
    }
}

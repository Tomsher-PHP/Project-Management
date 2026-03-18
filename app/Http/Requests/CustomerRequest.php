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
            'company_name' => 'required|string|max:255',
            'company_email' => ['nullable', 'email', 'max:150', Rule::unique('customers', 'company_email')->ignore($customerId)],
            'industry_id' => 'nullable|exists:industries,id',
            'website' => 'nullable|url|max:255',
            'registered_country_id' => 'nullable|exists:countries,id',
            'emirate' => 'nullable|string|max:255',
            'google_map_link' => 'nullable|url|max:255',
            'company_address' => 'nullable|string|max:65535',
            'sales_person' => 'nullable|string|max:255',
            'new_to_company' => 'boolean',
            'status' => 'boolean',

            'contacts' => 'array',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.email' => 'nullable|email|max:150',
            'contacts.*.landline' => 'nullable|string|max:255',
            'contacts.*.mobile' => 'nullable|string|max:255',
            'contacts.*.whatsapp' => 'nullable|string|max:255',
            'contacts.*.designation' => 'nullable|string|max:255',
            'contacts.*.is_primary' => 'boolean',
            'contacts.*.status' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required.',
            'company_email.email' => 'Please enter a valid email address.',
            'industry_id.exists' => 'Selected industry is invalid.',
            'registered_country_id.exists' => 'Selected country is invalid.',
            'website.url' => 'Please enter a valid website URL.',
            'google_map_link.url' => 'Please enter a valid Google Map link.',
            'contacts.array' => 'Contacts must be an array.',
            'contacts.*.name.required' => 'Contact name is required.',
            'contacts.*.email.email' => 'Please enter a valid email address for contact.',
            'contacts.*.is_primary.boolean' => 'Is primary must be true or false.',
            'contacts.*.status.boolean' => 'Status must be true or false.',
        ];
    }
}

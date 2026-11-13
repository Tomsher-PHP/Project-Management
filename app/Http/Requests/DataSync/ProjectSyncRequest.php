<?php

namespace App\Http\Requests\DataSync;

use App\Models\ApiClient;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProjectSyncRequest extends FormRequest
{
    /**
     * Maximum allowed projects in a single synchronization request.
     */
    public const MAX_PROJECTS_PER_REQUEST = 25;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var ApiClient|null $apiClient */
        $apiClient = $this->attributes->get('api_client');

        return $apiClient instanceof ApiClient && $apiClient->hasScope(ApiClient::SCOPE_PROJECTS_WRITE);
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Insufficient API permissions.',
        ], 403));
    }

    /**
     * Prepare data before validation rules are applied.
     */
    protected function prepareForValidation(): void
    {
        $customer = $this->input('customer');
        if (is_array($customer)) {
            if (isset($customer['external_id']) && $customer['external_id'] !== '' && ! is_array($customer['external_id'])) {
                $customer['external_id'] = (string) $customer['external_id'];
            }
            if (isset($customer['pms_customer_code']) && trim((string) $customer['pms_customer_code']) === '') {
                $customer['pms_customer_code'] = null;
            }
            $this->merge(['customer' => $customer]);
        }

        $projects = $this->input('projects');
        if (is_array($projects)) {
            foreach ($projects as $index => $project) {
                if (is_array($project)) {
                    if (isset($project['external_id']) && $project['external_id'] !== '' && ! is_array($project['external_id'])) {
                        $projects[$index]['external_id'] = (string) $project['external_id'];
                    }
                    if (isset($project['pms_project_code']) && trim((string) $project['pms_project_code']) === '') {
                        $projects[$index]['pms_project_code'] = null;
                    }
                }
            }
            $this->merge(['projects' => $projects]);
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
            // Customer Object
            'customer' => ['required', 'array'],
            'customer.external_code' => ['nullable', 'string', 'max:255'],
            'customer.external_id' => ['nullable', 'string', 'max:255'],
            'customer.pms_customer_code' => ['nullable', 'string', 'regex:/^CUS\d+$/i', 'max:255'],
            'customer.name' => ['required_without:customer.pms_customer_code', 'nullable', 'string', 'max:255'],
            'customer.email' => ['nullable', 'email', 'max:150'],
            'customer.industry_code' => ['nullable', 'digits:6'],
            'customer.website' => ['nullable', 'url', 'max:255'],
            'customer.country_code' => ['nullable', 'string', 'regex:/^[A-Za-z]{2,3}$/', 'max:3'],
            'customer.emirate' => ['nullable', 'string', 'max:255'],
            'customer.company_address' => ['nullable', 'string', 'max:65535'],
            'customer.sales_person_email' => ['nullable', 'email', 'max:150'],
            'customer.is_active' => ['nullable', 'boolean'],

            // Customer Contacts
            'customer.contacts' => ['required_without:customer.pms_customer_code', 'nullable', 'array'],
            'customer.contacts.*' => ['required', 'array'],
            'customer.contacts.*.name' => ['required', 'string', 'max:255'],
            'customer.contacts.*.email' => ['nullable', 'email', 'max:150'],
            'customer.contacts.*.mobile' => ['nullable', 'string', 'max:255'],
            'customer.contacts.*.whatsapp' => ['nullable', 'string', 'max:255'],
            'customer.contacts.*.landline' => ['nullable', 'string', 'max:255'],
            'customer.contacts.*.designation' => ['nullable', 'string', 'max:255'],
            'customer.contacts.*.is_primary' => ['required', 'boolean'],
            'customer.contacts.*.is_active' => ['nullable', 'boolean'],

            // Projects Array
            'projects' => ['required', 'array', 'min:1', 'max:' . self::MAX_PROJECTS_PER_REQUEST],
            'projects.*' => ['required', 'array'],
            'projects.*.name' => ['required', 'string', 'max:255'],
            'projects.*.external_code' => ['nullable', 'string', 'max:255'],
            'projects.*.external_id' => ['nullable', 'string', 'max:255'],
            'projects.*.pms_project_code' => ['nullable', 'string', 'regex:/^PRJ\d+$/i', 'max:255'],
            'projects.*.project_flow' => ['nullable', 'string', 'in:agile,linear'],
            'projects.*.priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'projects.*.status_code' => ['nullable', 'string', 'max:255'],
            'projects.*.stage_code' => ['nullable', 'string', 'max:255'],
            'projects.*.start_date' => ['nullable', 'date'],
            'projects.*.end_date' => ['nullable', 'date'],
            'projects.*.customer_end_date' => ['nullable', 'date'],
            'projects.*.estimated_time_minutes' => ['nullable', 'integer', 'min:0'],
            'projects.*.customer_estimate_minutes' => ['nullable', 'integer', 'min:0'],
            'projects.*.default_task_estimate_seconds' => ['nullable', 'integer', 'min:0'],
            'projects.*.domain' => ['nullable', 'string', 'max:255'],
            'projects.*.category_codes' => ['nullable', 'array'],
            'projects.*.technology_codes' => ['nullable', 'array'],
            'projects.*.service_type_codes' => ['nullable', 'array'],
            'projects.*.parent_project_code' => ['nullable', 'string', 'regex:/^PRJ\d+$/i', 'max:255'],
            'projects.*.sales_person_email' => ['nullable', 'email', 'max:150'],
            'projects.*.default_billable' => ['nullable', 'boolean'],
            'projects.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateCustomerContacts($validator);
            $this->validateProjectDates($validator);
        });
    }

    /**
     * Validate customer contacts rules.
     */
    protected function validateCustomerContacts(Validator $validator): void
    {
        $customer = $this->input('customer');
        if (! is_array($customer)) {
            return;
        }

        $pmsCustomerCode = trim((string) ($customer['pms_customer_code'] ?? ''));
        $hasPmsCustomerCode = $pmsCustomerCode !== '';
        $contacts = $customer['contacts'] ?? null;

        // If new customer and contacts is empty or not an array
        if (! $hasPmsCustomerCode) {
            if (! is_array($contacts) || empty($contacts)) {
                $validator->errors()->add('customer.contacts', 'At least one customer contact is required.');
                return;
            }
        } else {
            // Existing customer: contacts are optional
            if (! is_array($contacts) || empty($contacts)) {
                return;
            }
        }

        $primaryContacts = [];
        foreach ($contacts as $index => $contact) {
            if (! is_array($contact)) {
                continue;
            }

            $isPrimary = filter_var($contact['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isPrimary) {
                $primaryContacts[] = [
                    'index' => $index,
                    'contact' => $contact,
                ];
            }
        }

        $primaryCount = count($primaryContacts);

        if (! $hasPmsCustomerCode) {
            if ($primaryCount === 0) {
                $validator->errors()->add('customer.contacts', 'A primary contact is required.');
                return;
            }

            if ($primaryCount > 1) {
                $validator->errors()->add('customer.contacts', 'Multiple primary contacts are not allowed.');
                return;
            }

            $primary = $primaryContacts[0]['contact'];
            $primaryIndex = $primaryContacts[0]['index'];

            $hasCommunication = ! empty(trim((string) ($primary['email'] ?? '')))
                || ! empty(trim((string) ($primary['mobile'] ?? '')))
                || ! empty(trim((string) ($primary['whatsapp'] ?? '')))
                || ! empty(trim((string) ($primary['landline'] ?? '')));

            if (! $hasCommunication) {
                $validator->errors()->add(
                    "customer.contacts.{$primaryIndex}.email",
                    'The primary contact must contain at least one communication method (email, mobile, whatsapp, landline).'
                );
            }
        } else {
            // Existing customer: if contacts are supplied, reject multiple primary contacts
            if ($primaryCount > 1) {
                $validator->errors()->add('customer.contacts', 'Multiple primary contacts are not allowed.');
                return;
            }

            if ($primaryCount === 1) {
                $primary = $primaryContacts[0]['contact'];
                $primaryIndex = $primaryContacts[0]['index'];

                $hasCommunication = ! empty(trim((string) ($primary['email'] ?? '')))
                    || ! empty(trim((string) ($primary['mobile'] ?? '')))
                    || ! empty(trim((string) ($primary['whatsapp'] ?? '')))
                    || ! empty(trim((string) ($primary['landline'] ?? '')));

                if (! $hasCommunication) {
                    $validator->errors()->add(
                        "customer.contacts.{$primaryIndex}.email",
                        'The primary contact must contain at least one communication method (email, mobile, whatsapp, landline).'
                    );
                }
            }
        }
    }

    /**
     * Validate project date sequence rules.
     */
    protected function validateProjectDates(Validator $validator): void
    {
        $projects = $this->input('projects');
        if (! is_array($projects)) {
            return;
        }

        $timezone = (string) config('constants.timezone', config('app.timezone', 'UTC'));

        foreach ($projects as $index => $project) {
            if (! is_array($project)) {
                continue;
            }

            // Skip if date fields have raw validation failures
            if ($validator->errors()->has("projects.{$index}.start_date")
                || $validator->errors()->has("projects.{$index}.end_date")
                || $validator->errors()->has("projects.{$index}.customer_end_date")) {
                continue;
            }

            // Effective start date
            try {
                if (! empty($project['start_date'])) {
                    $effectiveStartDate = Carbon::parse($project['start_date'], $timezone)->startOfDay();
                } else {
                    $effectiveStartDate = Carbon::now($timezone)->startOfDay();
                }
            } catch (\Throwable) {
                continue;
            }

            // Effective end date
            try {
                if (! empty($project['end_date'])) {
                    $effectiveEndDate = Carbon::parse($project['end_date'], $timezone)->startOfDay();
                } else {
                    $effectiveEndDate = $effectiveStartDate->copy()->addDays(30);
                }
            } catch (\Throwable) {
                continue;
            }

            // Rule: end_date >= start_date (when end_date is supplied)
            if (! empty($project['end_date']) && $effectiveEndDate->lt($effectiveStartDate)) {
                $validator->errors()->add(
                    "projects.{$index}.end_date",
                    'The end date must be greater than or equal to the start date.'
                );
            }

            // Rule: customer_end_date >= end_date (when customer_end_date is supplied)
            if (! empty($project['customer_end_date'])) {
                try {
                    $effectiveCustomerEndDate = Carbon::parse($project['customer_end_date'], $timezone)->startOfDay();
                    if ($effectiveCustomerEndDate->lt($effectiveEndDate)) {
                        $validator->errors()->add(
                            "projects.{$index}.customer_end_date",
                            'The customer end date must be greater than or equal to the end date.'
                        );
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer.required' => 'The customer field is required.',
            'customer.array' => 'The customer must be an object.',
            'customer.name.required' => 'The customer name is required.',
            'customer.name.required_without' => 'The customer name is required.',
            'customer.email.email' => 'The customer email must be a valid email address.',
            'customer.website.url' => 'The customer website must be a valid URL.',
            'customer.industry_code.digits' => 'The industry code must be a 6-digit code.',
            'customer.country_code.regex' => 'The country code format is invalid.',
            'customer.pms_customer_code.regex' => 'The PMS customer code format is invalid.',
            'customer.contacts.required_without' => 'Customer contacts are required.',
            'customer.contacts.array' => 'Customer contacts must be an array.',
            'customer.contacts.*.name.required' => 'The contact name is required.',
            'customer.contacts.*.email.email' => 'The contact email must be a valid email address.',
            'customer.contacts.*.is_primary.required' => 'The contact primary flag is required.',
            'customer.contacts.*.is_primary.boolean' => 'The contact primary flag must be true or false.',
            'customer.contacts.*.is_active.boolean' => 'The contact active flag must be true or false.',
            'projects.required' => 'The projects field is required.',
            'projects.array' => 'The projects field must be an array.',
            'projects.min' => 'At least one project is required.',
            'projects.max' => 'The projects array may not contain more than :max projects.',
            'projects.*.required' => 'Each project must be an object.',
            'projects.*.array' => 'Each project must be an object.',
            'projects.*.name.required' => 'The project name is required.',
            'projects.*.pms_project_code.regex' => 'The PMS project code format is invalid.',
            'projects.*.parent_project_code.regex' => 'The parent project code format is invalid.',
            'projects.*.project_flow.in' => 'The selected project flow is invalid.',
            'projects.*.priority.in' => 'The selected priority is invalid.',
            'projects.*.status_code.digits' => 'The status code must be a 6-digit code.',
            'projects.*.stage_code.digits' => 'The stage code must be a 6-digit code.',
            'projects.*.category_codes.array' => 'Category codes must be an array.',
            'projects.*.category_codes.*.digits' => 'Each category code must be a 6-digit code.',
            'projects.*.technology_codes.array' => 'Technology codes must be an array.',
            'projects.*.technology_codes.*.digits' => 'Each technology code must be a 6-digit code.',
            'projects.*.service_type_codes.array' => 'Service type codes must be an array.',
            'projects.*.service_type_codes.*.digits' => 'Each service type code must be a 6-digit code.',
            'projects.*.sales_person_email.email' => 'The sales person email must be a valid email address.',
            'projects.*.start_date.date' => 'The start date must be a valid date.',
            'projects.*.end_date.date' => 'The end date must be a valid date.',
            'projects.*.customer_end_date.date' => 'The customer end date must be a valid date.',
            'projects.*.estimated_time_minutes.integer' => 'The estimated time must be an integer.',
            'projects.*.estimated_time_minutes.min' => 'The estimated time cannot be negative.',
            'projects.*.customer_estimate_minutes.integer' => 'The customer estimate must be an integer.',
            'projects.*.customer_estimate_minutes.min' => 'The customer estimate cannot be negative.',
            'projects.*.default_task_estimate_seconds.integer' => 'The default task estimate must be an integer.',
            'projects.*.default_task_estimate_seconds.min' => 'The default task estimate cannot be negative.',
            'projects.*.default_billable.boolean' => 'The default billable value must be true or false.',
            'projects.*.is_active.boolean' => 'The is active value must be true or false.',
        ];
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

    /**
     * Return normalized payload with PMS defaults applied.
     */
    public function normalizedPayload(): array
    {
        $timezone = (string) config('constants.timezone', config('app.timezone', 'UTC'));
        $customerData = (array) $this->input('customer', []);

        $normalizedCustomer = [
            'external_code' => isset($customerData['external_code']) && $customerData['external_code'] !== ''
                ? (string) $customerData['external_code'] : null,
            'external_id' => isset($customerData['external_id']) && $customerData['external_id'] !== ''
                ? (string) $customerData['external_id'] : null,
            'pms_customer_code' => isset($customerData['pms_customer_code']) && $customerData['pms_customer_code'] !== ''
                ? (string) $customerData['pms_customer_code'] : null,
            'name' => isset($customerData['name']) && $customerData['name'] !== ''
                ? (string) $customerData['name'] : null,
            'email' => isset($customerData['email']) && $customerData['email'] !== ''
                ? (string) $customerData['email'] : null,
            'industry_code' => isset($customerData['industry_code']) && $customerData['industry_code'] !== ''
                ? (string) $customerData['industry_code'] : null,
            'website' => isset($customerData['website']) && $customerData['website'] !== ''
                ? (string) $customerData['website'] : null,
            'country_code' => isset($customerData['country_code']) && $customerData['country_code'] !== ''
                ? strtoupper((string) $customerData['country_code']) : null,
            'emirate' => isset($customerData['emirate']) && $customerData['emirate'] !== ''
                ? (string) $customerData['emirate'] : null,
            'company_address' => isset($customerData['company_address']) && $customerData['company_address'] !== ''
                ? (string) $customerData['company_address'] : null,
            'sales_person_email' => isset($customerData['sales_person_email']) && $customerData['sales_person_email'] !== ''
                ? (string) $customerData['sales_person_email'] : null,
            'is_active' => isset($customerData['is_active'])
                ? filter_var($customerData['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
            'contacts' => [],
        ];

        if (! empty($customerData['contacts']) && is_array($customerData['contacts'])) {
            foreach ($customerData['contacts'] as $contact) {
                if (! is_array($contact)) {
                    continue;
                }
                $normalizedCustomer['contacts'][] = [
                    'name' => (string) ($contact['name'] ?? ''),
                    'email' => isset($contact['email']) && $contact['email'] !== '' ? (string) $contact['email'] : null,
                    'mobile' => isset($contact['mobile']) && $contact['mobile'] !== '' ? (string) $contact['mobile'] : null,
                    'whatsapp' => isset($contact['whatsapp']) && $contact['whatsapp'] !== '' ? (string) $contact['whatsapp'] : null,
                    'landline' => isset($contact['landline']) && $contact['landline'] !== '' ? (string) $contact['landline'] : null,
                    'designation' => isset($contact['designation']) && $contact['designation'] !== '' ? (string) $contact['designation'] : null,
                    'is_primary' => filter_var($contact['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'is_active' => isset($contact['is_active'])
                        ? filter_var($contact['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
                ];
            }
        }

        $normalizedProjects = [];
        $projectsData = (array) $this->input('projects', []);

        foreach ($projectsData as $project) {
            if (! is_array($project)) {
                continue;
            }

            // Start date calculation
            if (! empty($project['start_date'])) {
                $startDate = Carbon::parse($project['start_date'], $timezone)->format('Y-m-d');
            } else {
                $startDate = Carbon::now($timezone)->format('Y-m-d');
            }

            // End date calculation
            if (! empty($project['end_date'])) {
                $endDate = Carbon::parse($project['end_date'], $timezone)->format('Y-m-d');
            } else {
                $endDate = Carbon::parse($startDate, $timezone)->addDays(30)->format('Y-m-d');
            }

            // Customer end date calculation
            $customerEndDate = ! empty($project['customer_end_date'])
                ? Carbon::parse($project['customer_end_date'], $timezone)->format('Y-m-d')
                : null;

            $normalizedProjects[] = [
                'name' => (string) ($project['name'] ?? ''),
                'external_code' => isset($project['external_code']) && $project['external_code'] !== ''
                    ? (string) $project['external_code'] : null,
                'external_id' => isset($project['external_id']) && $project['external_id'] !== ''
                    ? (string) $project['external_id'] : null,
                'pms_project_code' => isset($project['pms_project_code']) && $project['pms_project_code'] !== ''
                    ? (string) $project['pms_project_code'] : null,
                'project_flow' => ! empty($project['project_flow']) ? (string) $project['project_flow'] : 'agile',
                'priority' => ! empty($project['priority']) ? (string) $project['priority'] : 'medium',
                'status_code' => isset($project['status_code']) && $project['status_code'] !== ''
                    ? (string) $project['status_code'] : null,
                'stage_code' => isset($project['stage_code']) && $project['stage_code'] !== ''
                    ? (string) $project['stage_code'] : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'customer_end_date' => $customerEndDate,
                'estimated_time_minutes' => isset($project['estimated_time_minutes']) && $project['estimated_time_minutes'] !== ''
                    ? (int) $project['estimated_time_minutes'] : null,
                'customer_estimate_minutes' => isset($project['customer_estimate_minutes']) && $project['customer_estimate_minutes'] !== ''
                    ? (int) $project['customer_estimate_minutes'] : null,
                'default_task_estimate_seconds' => isset($project['default_task_estimate_seconds']) && $project['default_task_estimate_seconds'] !== ''
                    ? (int) $project['default_task_estimate_seconds'] : null,
                'domain' => isset($project['domain']) && $project['domain'] !== '' ? (string) $project['domain'] : null,
                'category_codes' => ! empty($project['category_codes']) && is_array($project['category_codes'])
                    ? array_values(array_map('strval', $project['category_codes'])) : [],
                'technology_codes' => ! empty($project['technology_codes']) && is_array($project['technology_codes'])
                    ? array_values(array_map('strval', $project['technology_codes'])) : [],
                'service_type_codes' => ! empty($project['service_type_codes']) && is_array($project['service_type_codes'])
                    ? array_values(array_map('strval', $project['service_type_codes'])) : [],
                'parent_project_code' => isset($project['parent_project_code']) && $project['parent_project_code'] !== ''
                    ? (string) $project['parent_project_code'] : null,
                'sales_person_email' => ! empty($project['sales_person_email'])
                    ? (string) $project['sales_person_email']
                    : ($normalizedCustomer['sales_person_email'] ?? null),
                'default_billable' => isset($project['default_billable'])
                    ? filter_var($project['default_billable'], FILTER_VALIDATE_BOOLEAN) : true,
                'is_active' => isset($project['is_active'])
                    ? filter_var($project['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
            ];
        }

        return [
            'customer' => $normalizedCustomer,
            'projects' => $normalizedProjects,
        ];
    }
}


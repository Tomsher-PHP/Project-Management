<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * The attributes that should not be flashed for validation.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [];

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
        $user = $this->route('user');

        $userId = $user?->id;
        $userDetailId = $user?->details?->id;

        // works if route model binding: users.update/{user}
        $passwordRules = [
            Password::min(8)->letters()->numbers(),
        ];

        $email = !$userId ? [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($userId),
        ] : [];

        $rules = [
            // Basic Info
            'name' => ['required', 'string', 'max:255'],

            'email' => $email,

            // Profile Image
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048', // 2MB
            ],

            // Role / Department / Designation
            // 'role' => ['required', 'exists:roles,id'],
            'roles'   => ['required', 'array', 'min:1'],
            'roles.*' => [
                'required',
                'integer',
                'distinct',
                'exists:roles,id',
            ],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],

            // Personal Info
            'gender' => ['nullable', 'in:male,female,other'],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('user_details', 'phone')->ignore($userDetailId),],
            'whatsapp' => ['nullable', 'string', 'max:20', Rule::unique('user_details', 'whatsapp')->ignore($userDetailId)],

            // Emergency Contact
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_person_number' => ['nullable', 'string', 'max:20'],

            'emergency_contacts' => ['nullable', 'array'],
            'emergency_contacts.*' => ['array'],
            'emergency_contacts.*.name' => ['nullable', 'string', 'max:255'],
            'emergency_contacts.*.number' => [
                'nullable',
                'regex:/^\+?[0-9]+$/',
                'max:30',
            ],
            'emergency_contacts.*.relation' => [
                'nullable',
                'string',
                'max:100',
            ],

            // Native Contacts
            'native_contacts' => ['nullable', 'array'],
            'native_contacts.*' => ['array'],
            'native_contacts.*.name' => ['nullable', 'string', 'max:255'],
            'native_contacts.*.number' => [
                'nullable',
                'regex:/^\+?[0-9]+$/',
                'max:30',
            ],
            'native_contacts.*.relation' => [
                'nullable',
                'string',
                'max:100',
            ],

            // Dates
            'joining_date' => ['nullable', 'date'],
            'leaving_date' => ['nullable', 'date'],
            'dob' => ['nullable', 'date', 'before:today'],

            // Address
            'address' => ['nullable', 'string'],
            'native_address' => ['nullable', 'string'],

            // Personal Info & Preferences
            'marital_status' => ['nullable', 'string', 'max:30', Rule::in(array_keys(config('constants.marital_statuses', [])))],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:10', Rule::in(array_keys(config('constants.children_counts', [])))],
            'favorite_color' => ['nullable', 'string', 'max:30', Rule::in(array_keys(config('constants.favorite_colors', [])))],

            // Sizes
            'shirt_sizes' => ['nullable', 'array'],
            'shirt_sizes.casual' => ['nullable', 'string', Rule::in(array_keys(config('constants.shirt_sizes', [])))],
            'shirt_sizes.formal' => ['nullable', 'string', Rule::in(array_keys(config('constants.shirt_sizes', [])))],

            'shoe_sizes' => ['nullable', 'array'],
            'shoe_sizes.casual' => ['nullable', Rule::in(array_keys(config('constants.shoe_sizes', [])))],
            'shoe_sizes.formal' => ['nullable', Rule::in(array_keys(config('constants.shoe_sizes', [])))],

            // Reporting / Manager
            'reporter_id' => ['nullable', 'exists:users,id'],
            'manager_id' => ['nullable', 'exists:users,id'],

            // Employee ID
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('user_details', 'employee_id')->ignore($userDetailId),
            ],

            'remove_profile_image' => 'nullable',

            // KPI (multi select)
            'kpi_id' => ['nullable', 'array'],
            'kpi_id.*' => ['exists:kpis,id'],
        ];

        if ($this->isMethod('post')) {
            $rules['password'] = array_merge(['required', 'confirmed'], $passwordRules);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'emergency_contacts.*.number.regex' => 'The emergency contact number format is invalid.',
            'native_contacts.*.number.regex' => 'The native contact number format is invalid.',
            'shirt_sizes.casual.in' => 'The selected casual shirt size is invalid.',
            'shirt_sizes.formal.in' => 'The selected formal shirt size is invalid.',
            'shoe_sizes.casual.in' => 'The selected casual shoe size is invalid.',
            'shoe_sizes.formal.in' => 'The selected formal shoe size is invalid.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('password')) {
                $messages = $validator->errors()->get('password');
                $confirmMessages = array_filter($messages, fn($msg) => str_contains(strtolower($msg), 'confirm'));
                if (!empty($confirmMessages)) {
                    foreach ($confirmMessages as $msg) {
                        $validator->errors()->add('password_confirmation', $msg);
                    }
                    $remaining = array_values(array_filter($messages, fn($msg) => !str_contains(strtolower($msg), 'confirm')));
                    $validator->errors()->forget('password');
                    foreach ($remaining as $msg) {
                        $validator->errors()->add('password', $msg);
                    }
                }
            }

            // Check unexpected nested fields in emergency_contacts and native_contacts
            foreach (['emergency_contacts', 'native_contacts'] as $contactField) {
                $contacts = $this->input($contactField);
                if (is_array($contacts)) {
                    $allowedKeys = ['name', 'number', 'relation'];
                    foreach ($contacts as $index => $contact) {
                        if (is_array($contact)) {
                            $unexpected = array_diff(array_keys($contact), $allowedKeys);
                            if (!empty($unexpected)) {
                                $validator->errors()->add("{$contactField}.{$index}", 'Unexpected fields: ' . implode(', ', $unexpected));
                            }
                        }
                    }
                }
            }

            // Check unexpected nested fields in shirt_sizes and shoe_sizes
            foreach (['shirt_sizes', 'shoe_sizes'] as $sizeField) {
                $sizes = $this->input($sizeField);
                if (is_array($sizes)) {
                    $allowedKeys = ['casual', 'formal'];
                    $unexpected = array_diff(array_keys($sizes), $allowedKeys);
                    if (!empty($unexpected)) {
                        $validator->errors()->add($sizeField, 'Unexpected fields: ' . implode(', ', $unexpected));
                    }
                }
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $userId = $this->route('user') ?? null;
        // works if route model binding: users.update/{user}

        $email = !$userId ? [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($userId),
        ] : [];

        return [
            // Basic Info
            'name' => ['required', 'string', 'max:255'],

            'email' => $email,

            // Password
            'password' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                'min:6',
            ],

            // Profile Image
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048', // 2MB
            ],

            // Role / Department / Designation
            'role' => ['required', 'exists:roles,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],

            // Personal Info
            'gender' => ['nullable', 'in:male,female,other'],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('user_details', 'phone')->ignore($userId),],
            'whatsapp' => ['nullable', 'string', 'max:20', Rule::unique('user_details', 'whatsapp')->ignore($userId)],

            // Emergency Contact
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_person_number' => ['nullable', 'string', 'max:20'],

            // Dates
            'joining_date' => ['nullable', 'date'],
            'leaving_date' => ['nullable', 'date'],
            'dob' => ['nullable', 'date', 'before:today'],

            // Address
            'address' => ['nullable', 'string'],

            // Reporting / Manager
            'reporter_id' => ['nullable', 'exists:users,id'],
            'manager_id' => ['nullable', 'exists:users,id'],

            // Employee ID
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('user_details', 'employee_id')->ignore($userId),
            ],

            'remove_profile_image' => 'nullable',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RolePermissionRequest extends FormRequest
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
        $userTypes = implode(',', array_keys(config('constants.user_types')));
        $roleId = $this->role ?? null;

        $rules = [
            'name' => 'required|string|max:255|unique:roles,name,' . $roleId,
            'permissions' => 'array',
            'permissions.*' => 'string',
        ];

        // Only require user_type when creating
        if ($this->isMethod('post')) {
            $rules['user_type'] = "required|string|in:{$userTypes}";
        }

        return $rules;
    }
}

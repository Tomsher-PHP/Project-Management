<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethod('post')) {
            $this->merge(['code' => Department::generateUniqueCode()]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('department') ?? null;

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($id)],
            'sort_order' => ['required', 'numeric'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'digits:6', 'unique:departments,code'];
        }

        return $rules;
    }
}

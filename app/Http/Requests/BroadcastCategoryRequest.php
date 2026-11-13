<?php

namespace App\Http\Requests;

use App\Models\BroadcastCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BroadcastCategoryRequest extends FormRequest
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
            $this->merge(['code' => BroadcastCategory::generateUniqueCode()]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('broadcast_category');
        $id = $category instanceof BroadcastCategory ? $category->id : $category;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('broadcast_categories', 'name')->ignore($id),
            ],
            'color' => [
                'required',
                'string',
                'max:50',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
            'is_default' => [
                'nullable',
                'boolean',
            ],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'digits:6', 'unique:broadcast_categories,code'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required.',
            'name.unique' => 'This category name has already been taken.',
            'color.required' => 'The category color is required.',
            'color.regex' => 'The color must be a valid hex color code (e.g. #3B82F6).',
        ];
    }
}


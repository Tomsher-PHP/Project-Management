<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('expense_category') ?: null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'name')->whereNull('deleted_at')->ignore($id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}

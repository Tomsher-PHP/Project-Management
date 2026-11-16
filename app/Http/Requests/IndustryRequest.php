<?php

namespace App\Http\Requests;

use App\Models\Industry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndustryRequest extends FormRequest
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
            $this->merge(['code' => Industry::generateUniqueCode()]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('industry') ?? null;

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('industries', 'name')->ignore($id)],
            'parent_id' => ['nullable', 'exists:industries,id'],
            'sort_order' => ['required', 'numeric'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'digits:6', 'unique:industries,code'];
        }

        return $rules;
    }
}

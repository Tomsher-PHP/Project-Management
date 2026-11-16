<?php

namespace App\Http\Requests;

use App\Models\Technology;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TechnologyRequest extends FormRequest
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
            $this->merge(['code' => Technology::generateUniqueCode()]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('technology') ?? null;

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('technologies', 'name')->ignore($id)],
            'sort_order' => ['required', 'numeric'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'digits:6', 'unique:technologies,code'];
        }

        return $rules;
    }
}

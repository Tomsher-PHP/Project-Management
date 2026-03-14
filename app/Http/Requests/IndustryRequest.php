<?php

namespace App\Http\Requests;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('industry') ?? null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('industries', 'name')->ignore($id)],
            'parent_id' => ['nullable', 'exists:industries,id'],
            'order' => ['required', 'numeric'],
        ];
    }
}

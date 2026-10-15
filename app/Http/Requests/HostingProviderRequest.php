<?php

namespace App\Http\Requests;

use App\Models\HostingProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HostingProviderRequest extends FormRequest
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
        if ($this->isMethod('post') && empty($this->input('code'))) {
            $this->merge([
                'code' => HostingProvider::generateUniqueCode(),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('hosting_provider') ? $this->route('hosting_provider')->id : null;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hosting_providers', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'numeric'],
            'is_default' => ['nullable', 'boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = [
                'required',
                'digits:6',
                Rule::unique('hosting_providers', 'code')->whereNull('deleted_at'),
            ];
        } else {
            $rules['code'] = [
                'required',
                'digits:6',
                Rule::unique('hosting_providers', 'code')->ignore($id)->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }
}

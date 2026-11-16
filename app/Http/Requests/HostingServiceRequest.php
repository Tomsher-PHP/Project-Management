<?php

namespace App\Http\Requests;

use App\Models\HostingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HostingServiceRequest extends FormRequest
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
            $this->merge([
                'code' => HostingService::generateUniqueCode(),
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
        $id = $this->route('hosting_service') ? $this->route('hosting_service')->id : null;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hosting_services', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'numeric'],
            'is_default' => ['nullable', 'boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = [
                'required',
                'digits:6',
                Rule::unique('hosting_services', 'code')->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }
}

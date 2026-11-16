<?php

namespace App\Http\Requests\Settings;

use App\Services\ApiClientService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('data_sync_settings.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', Rule::in(ApiClientService::SUPPORTED_SCOPES)],
            'expiration_type' => ['required', 'string', Rule::in(['never', 'custom'])],
            'expires_at' => ['nullable', 'required_if:expiration_type,custom', 'date', 'after:now'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The application name is required.',
            'name.max' => 'The application name must not exceed 255 characters.',
            'scopes.required' => 'At least one permission scope must be selected.',
            'scopes.min' => 'At least one permission scope must be selected.',
            'scopes.*.in' => 'One or more selected permission scopes are invalid.',
            'expiration_type.required' => 'Please select an expiration type.',
            'expires_at.required_if' => 'Please select an expiration date.',
            'expires_at.after' => 'The expiration date must be a date in the future.',
        ];
    }
}


<?php

namespace App\Http\Requests\Settings;

use App\Models\DataSyncOutboundConnection;
use App\Services\DataSync\OutboundConnectionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOutboundConnectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('data_sync_settings.create') ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'base_url' => [
                'required',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    $error = OutboundConnectionService::validateDestinationUrl($value);
                    if ($error) {
                        $fail($error);
                    }
                },
            ],
            'auth_type' => [
                'required',
                'string',
                Rule::in([
                    DataSyncOutboundConnection::AUTH_TYPE_BEARER,
                    DataSyncOutboundConnection::AUTH_TYPE_API_KEY,
                    DataSyncOutboundConnection::AUTH_TYPE_BASIC,
                    DataSyncOutboundConnection::AUTH_TYPE_NONE,
                ]),
            ],
            'token' => ['nullable', 'required_if:auth_type,' . DataSyncOutboundConnection::AUTH_TYPE_BEARER, 'string'],
            'api_key' => ['nullable', 'required_if:auth_type,' . DataSyncOutboundConnection::AUTH_TYPE_API_KEY, 'string'],
            'header_name' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'required_if:auth_type,' . DataSyncOutboundConnection::AUTH_TYPE_BASIC, 'string', 'max:255'],
            'password' => ['nullable', 'required_if:auth_type,' . DataSyncOutboundConnection::AUTH_TYPE_BASIC, 'string'],
            'is_active' => ['sometimes', 'boolean'],
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
            'name.required' => 'The connection name is required.',
            'base_url.required' => 'The base URL is required.',
            'auth_type.required' => 'Please select an authentication type.',
            'token.required_if' => 'The bearer token is required for Bearer Token authentication.',
            'api_key.required_if' => 'The API key is required for API Key authentication.',
            'username.required_if' => 'The username is required for Basic Authentication.',
            'password.required_if' => 'The password is required for Basic Authentication.',
        ];
    }
}


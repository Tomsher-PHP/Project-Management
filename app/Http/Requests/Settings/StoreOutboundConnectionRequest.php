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
        $this->merge([
            'auth_type' => $this->input('auth_type') ?: DataSyncOutboundConnection::AUTH_TYPE_BEARER,
        ]);

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
                Rule::in([DataSyncOutboundConnection::AUTH_TYPE_BEARER]),
            ],
            'token' => ['required', 'string'],
            'api_client_id' => ['nullable', 'integer', 'exists:api_clients,id'],
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
            'auth_type.required' => 'The authentication type is required.',
            'auth_type.in' => 'Only Bearer Token authentication is supported.',
            'token.required' => 'The Bearer Token is required.',
        ];
    }
}


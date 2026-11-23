<?php

namespace App\Http\Requests\Settings;

use App\Models\DataSyncOutboundConnection;
use App\Services\DataSync\OutboundConnectionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOutboundApiRequest extends FormRequest
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
        $connection = $this->route('connection');
        $connectionId = $connection instanceof DataSyncOutboundConnection ? $connection->id : (int) $connection;
        $module = trim((string) $this->input('module'));

        return [
            'module' => ['required', 'string', 'max:100'],
            'operation' => [
                'required',
                'string',
                'max:50',
                Rule::unique('data_sync_outbound_apis', 'operation')
                    ->where(fn ($query) => $query->where('connection_id', $connectionId)->where('module', $module)),
            ],
            'method' => ['required', 'string', Rule::in(array_keys(OutboundConnectionService::AVAILABLE_METHODS))],
            'endpoint' => ['required', 'string', 'max:500'],
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
            'module.required' => 'The module is required.',
            'operation.required' => 'The operation is required.',
            'operation.unique' => 'An API configuration for this module and operation already exists for this connection.',
            'method.required' => 'The HTTP method is required.',
            'endpoint.required' => 'The API endpoint is required.',
        ];
    }
}


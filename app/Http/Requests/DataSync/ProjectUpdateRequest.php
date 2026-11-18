<?php

namespace App\Http\Requests\DataSync;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class ProjectUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('external_id') && $this->input('external_id') !== null && ! is_array($this->input('external_id'))) {
            $this->merge(['external_id' => (string) $this->input('external_id')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'project_flow' => ['sometimes', 'required', 'string', 'in:agile,linear'],
            'priority' => ['sometimes', 'required', 'string', 'in:low,medium,high,urgent'],
            'status_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stage_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'customer_end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'estimated_time_minutes' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'customer_estimate_minutes' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'default_task_estimate_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'domain' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sales_person_email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'parent_project_code' => ['sometimes', 'nullable', 'string', 'regex:/^PRJ\d+$/i', 'max:255'],
            'category_codes' => ['sometimes', 'nullable', 'array'],
            'category_codes.*' => ['string', 'max:255'],
            'technology_codes' => ['sometimes', 'nullable', 'array'],
            'technology_codes.*' => ['string', 'max:255'],
            'service_type_codes' => ['sometimes', 'nullable', 'array'],
            'service_type_codes.*' => ['string', 'max:255'],
            'default_billable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'external_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}


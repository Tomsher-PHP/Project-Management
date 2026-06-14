<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerProfileGradeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $descriptions = collect($this->input('descriptions', []))
            ->map(fn ($description) => is_string($description) ? trim($description) : $description)
            ->values()
            ->all();

        $this->merge(['descriptions' => $descriptions]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'customer_profile_grade_id' => [
                'required',
                'integer',
                Rule::exists('customer_profile_grades', 'id')->where(function ($query) use ($customer) {
                    $query->where('is_active', true);

                    if ($customer?->customer_profile_grade_id) {
                        $query->orWhere('id', $customer->customer_profile_grade_id);
                    }
                }),
            ],
            'descriptions' => ['nullable', 'array', 'max:20'],
            'descriptions.*' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_profile_grade_id.required' => 'Please select a profile grade.',
            'customer_profile_grade_id.exists' => 'The selected profile grade is unavailable.',
            'descriptions.max' => 'A grade can have a maximum of 20 description points.',
            'descriptions.*.max' => 'Each description point may not exceed 1000 characters.',
        ];
    }
}

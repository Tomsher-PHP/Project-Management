<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KpiRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $description = $this->input('description');

        if (is_string($description)) {
            $plainText = trim(str_replace("\xc2\xa0", ' ', strip_tags($description)));

            $this->merge([
                'description' => $plainText === '' ? null : $description,
            ]);
        }
    }

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
        $kpi = $this->route('kpi');
        $kpiId = is_object($kpi) ? $kpi->id : $kpi;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('kpis', 'name')->ignore($kpiId)],
            'description' => ['nullable', 'string'],
        ];
    }
}

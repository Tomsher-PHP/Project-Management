<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShiftRequest extends FormRequest
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
        $shift = $this->route('shift');
        $hasAssignments = $shift && $shift->assignments()->exists();

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shifts', 'name')->ignore($shift?->id),
            ],
            'color_code' => ['nullable', 'string'],
        ];

        // Time related rules
        $timeRules = [
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'break_duration' => ['required', 'integer', 'min:0'],
            'weekend_days' => ['nullable', 'array'],
            'weekend_days.*' => ['array'],
        ];

        // Apply time rules for create OR update when no assignments exist
        if ($this->isMethod('post') || !$hasAssignments) {
            $rules = array_merge($rules, $timeRules);
        }

        return $rules;
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleShiftRequest extends FormRequest
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
        return [
            'users' => ['required', 'array'],
            'users.*' => ['exists:users,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'date_from' => ['required', 'date', 'after_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'reason' => ['nullable', 'string'],
        ];
    }
}

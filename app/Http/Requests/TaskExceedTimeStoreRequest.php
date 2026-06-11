<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskExceedTimeStoreRequest extends FormRequest
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
            'new_estimated_time_minutes' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_estimated_time_minutes.required' => 'Please enter new estimated time.',
            'new_estimated_time_minutes.integer' => 'Please enter a valid new estimated time.',
            'new_estimated_time_minutes.min' => 'Please enter a valid new estimated time.',
            'reason.max' => 'Please enter a valid reason.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        $shiftId = $this->route('shift');

        return [
            'name' => 'required|string|max:255|unique:shifts,name,' . $shiftId,
            'departments' => 'nullable|array',
            'departments.*' => 'integer|exists:departments,id',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'break_duration' => 'required|integer|min:0',
            'weekend_days' => 'nullable|array',
            'weekend_days.*' => 'array',
        ];
    }
}

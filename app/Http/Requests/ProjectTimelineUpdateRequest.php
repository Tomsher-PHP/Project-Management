<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectTimelineUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:original,renewal,extension,new',
            'status' => 'required|in:1,2,3,4',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'customer_end_date' => 'nullable|date|after_or_equal:end_date',
            'estimated_time_minutes' => 'nullable|integer|min:0',
            'customer_estimate_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ];
    }
}

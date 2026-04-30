<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectPaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'nullable|numeric|min:0',
            'paid_date' => 'nullable|date',
            'coverage_start_date' => 'required|date',
            'coverage_end_date' => 'required|date|after_or_equal:coverage_start_date',
            'payment_method' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
        ];
    }
}

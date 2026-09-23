<?php

namespace App\Http\Requests;

use App\Models\Cheque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChequeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cheque_number' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'cheque_date' => ['required', 'date'],
            'cheque_given' => ['required', 'date'],
            'cheque_to' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'cheque_status' => [
                'required',
                'string',
                Rule::in(array_keys(Cheque::STATUS_OPTIONS)),
            ],
            'debited_date' => ['nullable', 'date'],
        ];
    }
}

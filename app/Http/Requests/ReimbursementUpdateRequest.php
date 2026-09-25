<?php

namespace App\Http\Requests;

use App\Models\Reimbursement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReimbursementUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'payment_mode_id' => [
                'required',
                'integer',
                Rule::exists('expense_payment_modes', 'id'),
            ],
            'vat_payment' => [
                'required',
                'string',
                Rule::in(array_keys(Reimbursement::VAT_PAYMENT_OPTIONS)),
            ],
            'local_intl_payment' => [
                'required',
                'string',
                Rule::in(array_keys(Reimbursement::LOCAL_INTL_OPTIONS)),
            ],
            'paid_date' => ['required', 'date'],
            'invoice_date' => ['nullable', 'date'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'other_currency' => ['nullable', 'required_with:other_amount', 'string', 'max:10'],
            'other_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_amount' => ['nullable', 'numeric', 'min:0'],
            'bank_charges' => ['nullable', 'numeric', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'service_provider_id' => [
                'nullable',
                'integer',
                Rule::exists('expense_service_providers', 'id'),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('expense_categories', 'id'),
            ],
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id'),
            ],
            'service_product' => ['nullable', 'string'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id'),
            ],
            'comment' => ['nullable', 'string'],
            'approval_status' => [
                'nullable',
                'string',
                Rule::in(array_keys(Reimbursement::APPROVAL_STATUS_OPTIONS)),
            ],
            'amount_reimbursed' => [
                'nullable',
                'string',
                Rule::in(array_keys(Reimbursement::AMOUNT_REIMBURSED_OPTIONS)),
            ],
            'reimbursement_mode' => [
                'nullable',
                'string',
                Rule::in(array_keys(Reimbursement::REIMBURSEMENT_MODE_OPTIONS)),
            ],
            'employee_confirmation' => [
                'nullable',
                'string',
                Rule::in(array_keys(Reimbursement::EMPLOYEE_CONFIRMATION_OPTIONS)),
            ],
        ];
    }
}

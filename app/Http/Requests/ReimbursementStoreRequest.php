<?php

namespace App\Http\Requests;

use App\Models\Reimbursement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReimbursementStoreRequest extends FormRequest
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
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'payment_mode_id' => [
                'required',
                'integer',
                Rule::exists('expense_payment_modes', 'id')->whereNull('deleted_at'),
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
                Rule::exists('expense_service_providers', 'id')->whereNull('deleted_at'),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('expense_categories', 'id')->whereNull('deleted_at'),
            ],
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')->whereNull('deleted_at'),
            ],
            'service_product' => ['nullable', 'string'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->whereNull('deleted_at'),
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

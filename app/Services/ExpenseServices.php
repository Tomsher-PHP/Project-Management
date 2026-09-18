<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpensePaymentMode;
use App\Models\ExpenseServiceProvider;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseServices
{
    public function getPaginatedExpenses(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? (int) ($filters['per_page'] ?? config('constants.per_page_count', 20));

        return Expense::query()
            ->with([
                'paymentMode',
                'serviceProvider',
                'category',
                'vendor',
                'customer',
                'addedBy',
                'updatedBy',
            ])
            ->filter($filters)
            ->sort($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createExpense(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $data['vat_amount'] = $data['vat_amount'] ?? 0.00;
            $data['bank_charges'] = $data['bank_charges'] ?? 0.00;

            return Expense::create($data);
        });
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $data['vat_amount'] = $data['vat_amount'] ?? 0.00;
            $data['bank_charges'] = $data['bank_charges'] ?? 0.00;

            $expense->update($data);

            return $expense->refresh();
        });
    }

    public function deleteExpense(Expense $expense): bool
    {
        return DB::transaction(function () use ($expense) {
            return (bool) $expense->delete();
        });
    }

    public function getFormOptions(): array
    {
        return [
            'payment_modes' => ExpensePaymentMode::query()->active()->orderBy('sort_order')->get(),
            'service_providers' => ExpenseServiceProvider::query()->active()->orderBy('sort_order')->get(),
            'categories' => ExpenseCategory::query()->active()->orderBy('sort_order')->get(),
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'customer_code']),
            'vat_payment_options' => Expense::VAT_PAYMENT_OPTIONS,
            'local_intl_options' => Expense::LOCAL_INTL_OPTIONS,
        ];
    }
}

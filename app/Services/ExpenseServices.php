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
            $data['vat_percentage'] = $this->calculateVatPercentage(
                $data['payment_amount'] ?? 0,
                $data['vat_amount']
            );

            return Expense::create($data);
        });
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $data['vat_amount'] = $data['vat_amount'] ?? $expense->vat_amount ?? 0.00;
            $data['bank_charges'] = $data['bank_charges'] ?? $expense->bank_charges ?? 0.00;

            $paymentAmount = $data['payment_amount'] ?? $expense->payment_amount ?? 0;
            $vatAmount = $data['vat_amount'];

            $data['vat_percentage'] = $this->calculateVatPercentage($paymentAmount, $vatAmount);

            $expense->update($data);

            return $expense->refresh();
        });
    }

    private function calculateVatPercentage(float|int|string $paymentAmount, float|int|string $vatAmount): float
    {
        $payment = (float) $paymentAmount;
        $vat = (float) $vatAmount;

        if ($payment > 0 && $vat > 0) {
            return round(($vat / $payment) * 100, 2);
        }

        return 0.00;
    }

    public function deleteExpense(Expense $expense): bool
    {
        return DB::transaction(function () use ($expense) {
            return (bool) $expense->delete();
        });
    }

    public function getFormOptions(): array
    {
        $paymentModes = ExpensePaymentMode::query()->active()->orderBy('sort_order')->get();
        $serviceProviders = ExpenseServiceProvider::query()->active()->orderBy('sort_order')->get();
        $categories = ExpenseCategory::query()->active()->orderBy('sort_order')->get();

        return [
            'payment_modes' => $paymentModes,
            'service_providers' => $serviceProviders,
            'categories' => $categories,
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'customer_code']),
            'vat_payment_options' => Expense::VAT_PAYMENT_OPTIONS,
            'local_intl_options' => Expense::LOCAL_INTL_OPTIONS,
            'default_payment_mode_id' => $paymentModes->firstWhere('is_default', true)?->id ?? '',
            'default_service_provider_id' => $serviceProviders->firstWhere('is_default', true)?->id ?? '',
            'default_category_id' => $categories->firstWhere('is_default', true)?->id ?? '',
        ];
    }
}

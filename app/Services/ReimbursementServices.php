<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\ExpensePaymentMode;
use App\Models\ExpenseServiceProvider;
use App\Models\Reimbursement;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReimbursementServices
{
    public function getPaginatedReimbursements(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? (int) ($filters['per_page'] ?? config('constants.per_page_count', 20));

        $query = Reimbursement::query()
            ->with([
                'user',
                'paymentMode',
                'serviceProvider',
                'category',
                'vendor',
                'customer',
                'addedBy',
                'updatedBy',
            ]);

        // Specific user filter handling (prevents conflict with generic Filterable trait relation logic)
        if (!empty($filters['user_id'])) {
            $userIds = (array) $filters['user_id'];
            $query->whereIn('user_id', $userIds);
            unset($filters['user_id']);
        }

        // Paid Date Range Filter
        $paidDateFrom = $filters['paid_date_from'] ?? $filters['paid_date_start'] ?? null;
        $paidDateTo = $filters['paid_date_to'] ?? $filters['paid_date_end'] ?? null;
        if (!empty($paidDateFrom)) {
            $query->whereDate('paid_date', '>=', $paidDateFrom);
        }
        if (!empty($paidDateTo)) {
            $query->whereDate('paid_date', '<=', $paidDateTo);
        }

        // Invoice Date Range Filter
        $invoiceDateFrom = $filters['invoice_date_from'] ?? $filters['invoice_date_start'] ?? null;
        $invoiceDateTo = $filters['invoice_date_to'] ?? $filters['invoice_date_end'] ?? null;
        if (!empty($invoiceDateFrom)) {
            $query->whereDate('invoice_date', '>=', $invoiceDateFrom);
        }
        if (!empty($invoiceDateTo)) {
            $query->whereDate('invoice_date', '<=', $invoiceDateTo);
        }

        return $query
            ->filter($filters)
            ->sort($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getReimbursementDetails(Reimbursement $reimbursement): Reimbursement
    {
        return $reimbursement->load([
            'user',
            'paymentMode',
            'serviceProvider',
            'category',
            'vendor',
            'customer',
            'addedBy',
            'updatedBy',
        ]);
    }

    public function createReimbursement(array $data): Reimbursement
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            $canChangeStatus = $user && ($user->is_super_admin || $user->can('reimbursement.status_change'));

            if (!$canChangeStatus) {
                // Unauthorized users cannot override defaults for protected status fields during creation
                $data['approval_status'] = Reimbursement::DEFAULT_APPROVAL_STATUS;
                $data['amount_reimbursed'] = Reimbursement::DEFAULT_AMOUNT_REIMBURSED;
                unset($data['reimbursement_mode']);
            } else {
                $data['approval_status'] = $data['approval_status'] ?? Reimbursement::DEFAULT_APPROVAL_STATUS;
                $data['amount_reimbursed'] = $data['amount_reimbursed'] ?? Reimbursement::DEFAULT_AMOUNT_REIMBURSED;
            }

            $data['employee_confirmation'] = $data['employee_confirmation'] ?? Reimbursement::DEFAULT_EMPLOYEE_CONFIRMATION;

            $data['vat_amount'] = $data['vat_amount'] ?? 0.00;
            $data['bank_charges'] = $data['bank_charges'] ?? 0.00;
            $data['vat_percentage'] = $this->calculateVatPercentage(
                $data['payment_amount'] ?? 0,
                $data['vat_amount']
            );

            return Reimbursement::create($data);
        });
    }

    public function updateReimbursement(Reimbursement $reimbursement, array $data): Reimbursement
    {
        return DB::transaction(function () use ($reimbursement, $data) {
            $user = Auth::user();
            $canChangeStatus = $user && ($user->is_super_admin || $user->can('reimbursement.status_change'));

            if (!$canChangeStatus) {
                // Unauthorized users cannot modify protected status fields
                unset($data['approval_status'], $data['amount_reimbursed'], $data['reimbursement_mode']);
            }

            $data['vat_amount'] = $data['vat_amount'] ?? $reimbursement->vat_amount ?? 0.00;
            $data['bank_charges'] = $data['bank_charges'] ?? $reimbursement->bank_charges ?? 0.00;

            $paymentAmount = $data['payment_amount'] ?? $reimbursement->payment_amount ?? 0;
            $vatAmount = $data['vat_amount'];

            $data['vat_percentage'] = $this->calculateVatPercentage($paymentAmount, $vatAmount);

            $reimbursement->update($data);

            return $reimbursement->refresh();
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

    public function deleteReimbursement(Reimbursement $reimbursement): bool
    {
        return DB::transaction(function () use ($reimbursement) {
            return (bool) $reimbursement->delete();
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
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'vat_payment_options' => Reimbursement::VAT_PAYMENT_OPTIONS,
            'local_intl_options' => Reimbursement::LOCAL_INTL_OPTIONS,
            'approval_status_options' => Reimbursement::APPROVAL_STATUS_OPTIONS,
            'amount_reimbursed_options' => Reimbursement::AMOUNT_REIMBURSED_OPTIONS,
            'reimbursement_mode_options' => Reimbursement::REIMBURSEMENT_MODE_OPTIONS,
            'employee_confirmation_options' => Reimbursement::EMPLOYEE_CONFIRMATION_OPTIONS,
            'default_payment_mode_id' => $paymentModes->firstWhere('is_default', true)?->id ?? '',
            'default_service_provider_id' => $serviceProviders->firstWhere('is_default', true)?->id ?? '',
            'default_category_id' => $categories->firstWhere('is_default', true)?->id ?? '',
            'default_approval_status' => Reimbursement::DEFAULT_APPROVAL_STATUS,
            'default_amount_reimbursed' => Reimbursement::DEFAULT_AMOUNT_REIMBURSED,
            'default_employee_confirmation' => Reimbursement::DEFAULT_EMPLOYEE_CONFIRMATION,
        ];
    }
}

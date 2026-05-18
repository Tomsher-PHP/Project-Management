<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CustomerRestoreService
{
    public function getDeletedCustomers(int $perPage): LengthAwarePaginator
    {
        return Customer::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findDeletedCustomerOrFail(int|string $id): Customer
    {
        return Customer::onlyTrashed()->findOrFail($id);
    }

    public function hasActiveEmailConflict(Customer $customer): bool
    {
        if (empty($customer->email)) {
            return false;
        }

        return Customer::where('email', $customer->email)->exists();
    }

    public function restoreDeletedCustomer(Customer $customer): bool
    {
        if ($this->hasActiveEmailConflict($customer)) {
            return false;
        }

        $this->markAsRestored($customer);

        return true;
    }

    public function bulkRestoreCustomers(array $customerIds): array
    {
        $customers = Customer::onlyTrashed()
            ->whereIn('id', $customerIds)
            ->get();

        if ($customers->isEmpty()) {
            return [
                'selected_count' => 0,
                'restored_count' => 0,
                'conflicting_count' => 0,
            ];
        }

        $conflictingEmails = $this->getConflictingEmails($customers);

        $conflictingCustomers = $customers->filter(function (Customer $customer) use ($conflictingEmails) {
            return !empty($customer->email) && $conflictingEmails->contains($customer->email);
        });

        $restorableCustomers = $customers->reject(function (Customer $customer) use ($conflictingEmails) {
            return !empty($customer->email) && $conflictingEmails->contains($customer->email);
        });

        $restorableCustomers->each(function (Customer $customer) {
            $this->markAsRestored($customer);
        });

        return [
            'selected_count' => $customers->count(),
            'restored_count' => $restorableCustomers->count(),
            'conflicting_count' => $conflictingCustomers->count(),
        ];
    }

    private function getConflictingEmails(Collection $customers)
    {
        $emails = $customers->pluck('email')->filter()->unique();

        if ($emails->isEmpty()) {
            return collect();
        }

        return Customer::whereIn('email', $emails)
            ->pluck('email');
    }

    private function markAsRestored(Customer $customer): void
    {
        $customer->restore();
        $customer->contacts()->withTrashed()->restore();
        $customer->update([
            'is_active' => true,
        ]);
    }
}

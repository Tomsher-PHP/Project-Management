<?php

namespace App\Services;

use App\Models\Cheque;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ChequeServices
{
    public function getPaginatedCheques(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? (int) ($filters['per_page'] ?? config('constants.per_page_count', 20));

        $query = Cheque::query()
            ->with([
                'addedBy',
                'updatedBy',
            ]);

        // Cheque Date Range Filter
        $chequeDateFrom = $filters['cheque_date_from'] ?? $filters['cheque_date_start'] ?? null;
        $chequeDateTo = $filters['cheque_date_to'] ?? $filters['cheque_date_end'] ?? null;
        if (!empty($chequeDateFrom)) {
            $query->whereDate('cheque_date', '>=', $chequeDateFrom);
        }
        if (!empty($chequeDateTo)) {
            $query->whereDate('cheque_date', '<=', $chequeDateTo);
        }

        // Cheque Given Date Range Filter
        $chequeGivenFrom = $filters['cheque_given_from'] ?? $filters['cheque_given_start'] ?? null;
        $chequeGivenTo = $filters['cheque_given_to'] ?? $filters['cheque_given_end'] ?? null;
        if (!empty($chequeGivenFrom)) {
            $query->whereDate('cheque_given', '>=', $chequeGivenFrom);
        }
        if (!empty($chequeGivenTo)) {
            $query->whereDate('cheque_given', '<=', $chequeGivenTo);
        }

        // Debited Date Range Filter
        $debitedDateFrom = $filters['debited_date_from'] ?? $filters['debited_date_start'] ?? null;
        $debitedDateTo = $filters['debited_date_to'] ?? $filters['debited_date_end'] ?? null;
        if (!empty($debitedDateFrom)) {
            $query->whereDate('debited_date', '>=', $debitedDateFrom);
        }
        if (!empty($debitedDateTo)) {
            $query->whereDate('debited_date', '<=', $debitedDateTo);
        }

        return $query
            ->filter($filters)
            ->sort($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getChequeDetails(Cheque $cheque): Cheque
    {
        return $cheque->load([
            'addedBy',
            'updatedBy',
        ]);
    }

    public function createCheque(array $data): Cheque
    {
        return DB::transaction(function () use ($data) {
            $data['cheque_status'] = $data['cheque_status'] ?? Cheque::DEFAULT_STATUS;

            return Cheque::create($data);
        });
    }

    public function updateCheque(Cheque $cheque, array $data): Cheque
    {
        return DB::transaction(function () use ($cheque, $data) {
            $cheque->update($data);

            return $cheque->refresh();
        });
    }

    public function deleteCheque(Cheque $cheque): bool
    {
        return DB::transaction(function () use ($cheque) {
            return (bool) $cheque->delete();
        });
    }

    public function getFormOptions(): array
    {
        return [
            'status_options' => Cheque::STATUS_OPTIONS,
            'default_status' => Cheque::DEFAULT_STATUS,
        ];
    }
}

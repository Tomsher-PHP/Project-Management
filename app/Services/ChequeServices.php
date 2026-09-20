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

        return Cheque::query()
            ->with([
                'addedBy',
                'updatedBy',
            ])
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

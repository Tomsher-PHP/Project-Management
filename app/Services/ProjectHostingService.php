<?php

namespace App\Services;

use App\Models\HostingServiceStatus;
use App\Models\Project;
use App\Models\ProjectHostingRecord;
use App\Models\ProjectHostingRenewalHistory;
use App\Models\ProjectHostingStatusHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectHostingService
{
    /**
     * Create a new hosting record with its initial status history entry.
     */
    public function createRecord(Project $project, array $data, ?int $userId = null): ProjectHostingRecord
    {
        $userId = $userId ?? auth()->id();
        $data['project_id'] = $project->id;
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        unset($data['last_renewed_date']);

        return DB::transaction(function () use ($data, $userId) {
            $record = ProjectHostingRecord::create($data);

            ProjectHostingStatusHistory::create([
                'project_hosting_record_id' => $record->id,
                'from_status_id' => null,
                'to_status_id' => $record->hosting_service_status_id,
                'changed_at' => now(),
                'changed_by' => $userId,
                'reason' => 'Initial status',
                'notes' => null,
            ]);

            return $record;
        });
    }

    /**
     * Update an existing hosting record and record status transition if status changed.
     */
    public function updateRecord(ProjectHostingRecord $hostingRecord, array $data, ?int $userId = null): ProjectHostingRecord
    {
        $userId = $userId ?? auth()->id();
        $data['updated_by'] = $userId;
        unset($data['last_renewed_date']);

        return DB::transaction(function () use ($hostingRecord, $data, $userId) {
            /** @var ProjectHostingRecord $lockedRecord */
            $lockedRecord = ProjectHostingRecord::where('id', $hostingRecord->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatusId = (int) $lockedRecord->hosting_service_status_id;
            $newStatusId = isset($data['hosting_service_status_id']) ? (int) $data['hosting_service_status_id'] : null;

            $lockedRecord->update($data);

            if ($newStatusId !== null && $oldStatusId !== $newStatusId) {
                ProjectHostingStatusHistory::create([
                    'project_hosting_record_id' => $lockedRecord->id,
                    'from_status_id' => $oldStatusId,
                    'to_status_id' => $newStatusId,
                    'changed_at' => now(),
                    'changed_by' => $userId,
                    'reason' => 'Status changed',
                    'notes' => null,
                ]);
            }

            return $lockedRecord;
        });
    }

    /**
     * Update only the status of a hosting record with audit history.
     */
    public function updateStatus(ProjectHostingRecord $hostingRecord, int $newStatusId, string $reason = 'Status changed via dropdown', ?string $notes = null, ?int $userId = null): ProjectHostingRecord
    {
        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use ($hostingRecord, $newStatusId, $reason, $notes, $userId) {
            /** @var ProjectHostingRecord $lockedRecord */
            $lockedRecord = ProjectHostingRecord::where('id', $hostingRecord->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatusId = (int) $lockedRecord->hosting_service_status_id;

            if ($oldStatusId !== $newStatusId) {
                $lockedRecord->update([
                    'hosting_service_status_id' => $newStatusId,
                    'updated_by' => $userId,
                ]);

                ProjectHostingStatusHistory::create([
                    'project_hosting_record_id' => $lockedRecord->id,
                    'from_status_id' => $oldStatusId,
                    'to_status_id' => $newStatusId,
                    'changed_at' => now(),
                    'changed_by' => $userId,
                    'reason' => $reason,
                    'notes' => $notes,
                ]);
            }

            return $lockedRecord->load('hostingServiceStatus');
        });
    }

    /**
     * Process renewal for a hosting record, creating renewal history and updating dates.
     */
    public function renewRecord(ProjectHostingRecord $hostingRecord, array $data, ?int $userId = null): ProjectHostingRecord
    {
        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use ($hostingRecord, $data, $userId) {
            /** @var ProjectHostingRecord $lockedRecord */
            $lockedRecord = ProjectHostingRecord::where('id', $hostingRecord->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Capture previous record state for history tracking
            $previousStartDate = $lockedRecord->start_date;
            $previousExpiryDate = $lockedRecord->expiry_date;

            // Determine updated dates
            $renewalDate = $data['renewal_date'];
            $newExpiryDate = $data['new_expiry_date'];
            $newStartDate = !empty($data['new_start_date']) ? $data['new_start_date'] : $lockedRecord->start_date;

            // Determine updated billing details (preserve existing if not explicitly provided)
            $billingCycleId = array_key_exists('hosting_billing_cycle_id', $data) && $data['hosting_billing_cycle_id'] !== null
                ? (int) $data['hosting_billing_cycle_id']
                : $lockedRecord->hosting_billing_cycle_id;

            $billingAmount = array_key_exists('billing_amount', $data) && $data['billing_amount'] !== null && $data['billing_amount'] !== ''
                ? $data['billing_amount']
                : $lockedRecord->billing_amount;

            $currencyCode = !empty($data['currency_code'])
                ? $data['currency_code']
                : $lockedRecord->currency_code;

            $reference = array_key_exists('reference', $data) && $data['reference'] !== null && $data['reference'] !== ''
                ? $data['reference']
                : $lockedRecord->reference;

            $notes = $data['notes'] ?? null;

            // Create Renewal History record
            ProjectHostingRenewalHistory::create([
                'project_hosting_record_id' => $lockedRecord->id,
                'renewal_date' => $renewalDate,
                'previous_start_date' => $previousStartDate,
                'previous_expiry_date' => $previousExpiryDate,
                'new_start_date' => $newStartDate,
                'new_expiry_date' => $newExpiryDate,
                'hosting_billing_cycle_id' => $billingCycleId,
                'billing_amount' => $billingAmount,
                'currency_code' => $currencyCode,
                'reference' => $reference,
                'notes' => $notes,
                'renewed_by' => $userId,
            ]);

            // Update hosting record with new renewal values
            $recordUpdates = [
                'last_renewed_date' => $renewalDate,
                'expiry_date' => $newExpiryDate,
                'next_renewal_date' => $newExpiryDate, // In PMS, next renewal aligns with expiry date
                'hosting_billing_cycle_id' => $billingCycleId,
                'billing_amount' => $billingAmount,
                'currency_code' => $currencyCode,
                'reference' => $reference,
                'updated_by' => $userId,
            ];

            if (!empty($data['new_start_date'])) {
                $recordUpdates['start_date'] = $data['new_start_date'];
            }

            $lockedRecord->update($recordUpdates);

            // Reload relations for rendering updated drawer view
            $lockedRecord->load([
                'hostingProvider',
                'hostingService',
                'hostingServiceStatus',
                'hostingBillingCycle',
                'hostingEnvironment',
                'creator',
                'updater',
            ]);

            return $lockedRecord;
        });
    }

    /**
     * Delete a hosting record.
     */
    public function deleteRecord(ProjectHostingRecord $hostingRecord): bool
    {
        return DB::transaction(function () use ($hostingRecord) {
            return (bool) $hostingRecord->delete();
        });
    }

    /**
     * Calculate 4 summary metrics for the given project.
     */
    public function calculateSummaryMetrics(Project $project): array
    {
        $baseQuery = ProjectHostingRecord::query()->where('project_id', $project->id);

        $today = now()->startOfDay()->toDateString();
        $in30Days = now()->addDays(30)->endOfDay()->toDateString();

        $total = (clone $baseQuery)->count();

        // Active status count (determined from HostingServiceStatus active code/name)
        $activeStatusIds = HostingServiceStatus::where('code', 'active')
            ->orWhere('name', 'Active')
            ->pluck('id');

        $active = (clone $baseQuery)
            ->whereIn('hosting_service_status_id', $activeStatusIds)
            ->count();

        $expiringSoon = (clone $baseQuery)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $in30Days])
            ->count();

        $expired = (clone $baseQuery)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
        ];
    }

    /**
     * Query builder for project hosting records with eager loaded relations and filters.
     */
    public function getHostingRecordsQuery(Project $project, array $filters = []): Builder
    {
        $query = ProjectHostingRecord::query()
            ->where('project_id', $project->id)
            ->with([
                'hostingProvider',
                'hostingService',
                'hostingServiceStatus',
                'hostingBillingCycle',
                'hostingEnvironment',
                'creator:id,name',
                'updater:id,name',
            ])
            ->latest('id');

        // Search by keyword (name, domain, reference)
        if (!empty($filters['search'])) {
            $searchTerm = trim((string) $filters['search']);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('domain', 'like', "%{$searchTerm}%")
                    ->orWhere('reference', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by provider
        if (!empty($filters['hosting_provider_id'])) {
            $query->where('hosting_provider_id', (int) $filters['hosting_provider_id']);
        }

        // Filter by service type
        if (!empty($filters['hosting_service_id'])) {
            $query->where('hosting_service_id', (int) $filters['hosting_service_id']);
        }

        // Filter by status
        if (!empty($filters['hosting_service_status_id'])) {
            $query->where('hosting_service_status_id', (int) $filters['hosting_service_status_id']);
        }

        // Filter by ownership
        if (!empty($filters['ownership_type'])) {
            $query->where('ownership_type', $filters['ownership_type']);
        }

        // Filter by expiry classification
        if (!empty($filters['expiry_status'])) {
            $today = now()->startOfDay()->toDateString();
            $in30Days = now()->addDays(30)->endOfDay()->toDateString();

            match ($filters['expiry_status']) {
                'expiring_soon' => $query->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [$today, $in30Days]),
                'expired' => $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', $today),
                'no_expiry' => $query->whereNull('expiry_date'),
                default => null,
            };
        }

        return $query;
    }

    /**
     * Get filtered and paginated hosting records.
     */
    public function getFilteredHostingRecords(Project $project, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $perPage = max(1, min(100, $perPage));

        return $this->getHostingRecordsQuery($project, $filters)->paginate($perPage);
    }

    /**
     * Get renewal history collection for a hosting record.
     */
    public function getRenewalHistory(ProjectHostingRecord $hostingRecord): Collection
    {
        return $hostingRecord->renewalHistories()
            ->with(['billingCycle', 'renewedBy'])
            ->orderByDesc('renewal_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Get status history collection for a hosting record.
     */
    public function getStatusHistory(ProjectHostingRecord $hostingRecord): Collection
    {
        return $hostingRecord->statusHistories()
            ->with(['fromStatus', 'toStatus', 'changedBy'])
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->get();
    }
}

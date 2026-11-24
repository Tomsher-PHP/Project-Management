<?php

namespace App\Observers;

use App\Services\DataSync\OutboundDataSyncService;
use Illuminate\Database\Eloquent\Model;

class DataSyncOutboundObserver
{
    /**
     * Ensure observer events are processed only after active database transactions successfully commit.
     * If a transaction is rolled back, no outbound event will be triggered.
     *
     * @var bool
     */
    public bool $afterCommit = true;

    public function __construct(
        protected OutboundDataSyncService $syncService
    ) {}

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->handleEvent('create', $model);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $module = $this->resolveModule($model);
        if (! $module) {
            return;
        }

        // Avoid dispatching outbound synchronization when no relevant business fields have changed
        $relevantFields = config("data_sync_outbound.relevant_fields.{$module}", []);
        if (! empty($relevantFields)) {
            $changes = array_keys($model->getChanges());
            $hasRelevantChanges = count(array_intersect($changes, $relevantFields)) > 0;

            if (! $hasRelevantChanges) {
                return;
            }
        }

        $this->handleEvent('update', $model, $module);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->handleEvent('delete', $model);
    }

    /**
     * Dispatch the model event to OutboundDataSyncService.
     */
    protected function handleEvent(string $operation, Model $model, ?string $resolvedModule = null): void
    {
        $module = $resolvedModule ?? $this->resolveModule($model);
        if (! $module) {
            return;
        }

        $this->syncService->sync($module, $operation, $model);
    }

    /**
     * Resolve the outbound module key for the given Eloquent model.
     */
    public function resolveModule(Model $model): ?string
    {
        $modelMap = config('data_sync_outbound.models', []);

        foreach ($modelMap as $modelClass => $moduleKey) {
            if ($model instanceof $modelClass) {
                return $moduleKey;
            }
        }

        return null;
    }
}


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
        if ($model->exists) {
            $model->refresh();
        }

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

        if ($module === 'projects' && $model instanceof \App\Models\Project) {
            $this->handleProjectUpdated($model);

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
     * Handle Project-specific update events, isolating status_update and details update operations.
     */
    protected function handleProjectUpdated(\App\Models\Project $project): void
    {
        $changes = array_keys($project->getChanges());

        $statusChanged = in_array('status_id', $changes, true);

        $detailFields = config('data_sync_outbound.relevant_fields.project_details', [
            'name',
            'start_date',
            'end_date',
            'customer_end_date',
            'estimated_time_seconds',
            'customer_estimate_seconds',
            'project_category_ids',
        ]);

        $detailsChanged = count(array_intersect($changes, $detailFields)) > 0;

        // If only status changed -> dispatch status_update
        // If only details changed -> dispatch update
        // If both changed -> dispatch both status_update and update
        if ($statusChanged) {
            $this->handleEvent('status_update', $project, 'projects');
        }

        if ($detailsChanged) {
            $this->handleEvent('update', $project, 'projects');
        }
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


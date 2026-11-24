<?php

namespace App\Services\DataSync;

use App\Jobs\DataSync\SendOutboundApiJob;
use App\Models\ApiClient;
use App\Models\DataSyncEntityMapping;
use App\Models\DataSyncOutboundApi;
use App\Models\DataSyncOutboundConnection;
use App\Models\DataSyncOutboundHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class OutboundDataSyncService
{
    /**
     * Optional explicitly set originating API client for loop suppression during background/service operations.
     */
    protected static ?ApiClient $originatingClient = null;

    public function __construct(
        protected OutboundPayloadService $payloadService,
        protected OutboundHttpService $httpService
    ) {}

    /**
     * Set the current originating API client to prevent loopbacks.
     */
    public static function setOriginatingClient(?ApiClient $client): void
    {
        static::$originatingClient = $client;
    }

    /**
     * Safely execute a callback scoped to an originating client, restoring state after completion.
     */
    public static function withOriginatingClient(?ApiClient $client, callable $callback): mixed
    {
        $previous = static::$originatingClient;
        static::$originatingClient = $client;

        try {
            return $callback();
        } finally {
            static::$originatingClient = $previous;
        }
    }

    /**
     * Get the resolved originating API client from context or current request.
     */
    public function getOriginatingClient(): ?ApiClient
    {
        if (static::$originatingClient !== null) {
            return static::$originatingClient;
        }

        if (function_exists('request') && request() !== null) {
            $client = request()->attributes->get('api_client');
            if ($client instanceof ApiClient) {
                return $client;
            }
        }

        return null;
    }

    /**
     * Handle model change event and dispatch outbound synchronization across active connections.
     *
     * @param  string  $module
     * @param  string  $operation  ('create', 'update', 'delete')
     * @param  Model  $model
     * @return list<DataSyncOutboundHistory> Created history records
     */
    public function sync(string $module, string $operation, Model $model): array
    {
        // 1. Find all active, non-deleted outbound connections with active APIs for this module
        $connections = $this->getActiveConnectionsForModule($module);
        if ($connections->isEmpty()) {
            return [];
        }

        $originatingClient = $this->getOriginatingClient();
        $createdHistories = [];

        foreach ($connections as $connection) {
            // 2. Prevent synchronization loops back to the originating external system
            if ($this->isOriginatingExternalSystem($connection, $originatingClient, $module, $model)) {
                continue;
            }

            // 3. Resolve the matching outbound operation and API for this connection
            $matchingApi = $this->resolveMatchingApi($connection, $operation, $module, $model);
            if (! $matchingApi) {
                // If no active matching API exists, skip without creating history
                continue;
            }

            // 4. Prepare delivery for this connection and API
            $history = $this->createAndDispatchDelivery($connection, $matchingApi, $model);
            if ($history) {
                $createdHistories[] = $history;
            }
        }

        return $createdHistories;
    }

    /**
     * Create delivery history record and dispatch SendOutboundApiJob.
     */
    protected function createAndDispatchDelivery(
        DataSyncOutboundConnection $connection,
        DataSyncOutboundApi $api,
        Model $model
    ): ?DataSyncOutboundHistory {
        $resolvedOperation = $api->operation;

        // Generate JSON payload
        $payload = $this->payloadService->buildPayload($api->module, $resolvedOperation, $model, $connection);

        // Check for duplicate pending delivery with identical payload to avoid redundant jobs
        $existingPending = DataSyncOutboundHistory::query()
            ->where('outbound_api_id', $api->id)
            ->where('entity_id', $model->id)
            ->where('status', DataSyncOutboundHistory::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($existingPending && $existingPending->request_payload === $payload) {
            return $existingPending;
        }

        // Construct destination URL and resolve placeholders
        try {
            $requestUrl = $this->httpService->buildUrl($connection, $api, $model);
        } catch (Throwable $e) {
            // If an identifier cannot be resolved or URL is invalid, record failed history without dispatching
            Log::warning('Outbound Data Sync URL resolution failed', [
                'connection_id' => $connection->id,
                'api_id' => $api->id,
                'module' => $api->module,
                'entity_id' => $model->id,
                'error' => $e->getMessage(),
            ]);

            return DataSyncOutboundHistory::create([
                'outbound_api_id' => $api->id,
                'entity_id' => $model->id,
                'request_url' => rtrim($connection->base_url, '/') . '/' . ltrim($api->endpoint, '/'),
                'request_payload' => $payload,
                'status' => DataSyncOutboundHistory::STATUS_FAILED,
                'error_message' => 'URL Construction Error: ' . $e->getMessage(),
            ]);
        }

        // Create pending history record
        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $api->id,
            'entity_id' => $model->id,
            'request_url' => $requestUrl,
            'request_payload' => $payload,
            'status' => DataSyncOutboundHistory::STATUS_PENDING,
        ]);

        // Dispatch background queue job
        $queueName = config('data_sync_outbound.queue', 'default');
        try {
            SendOutboundApiJob::dispatch($history->id)->afterCommit()->onQueue($queueName);
        } catch (Throwable $e) {
            Log::error('Outbound Data Sync job dispatch failure', [
                'history_id' => $history->id,
                'error' => $e->getMessage(),
            ]);
            // History record remains pending and can be recovered by retryPendingDeliveries()
        }

        return $history;
    }

    /**
     * Resolve the appropriate active API configuration according to priority rules:
     *
     * Created:
     *   If Create API exists -> Create
     *   Else if Sync API exists -> Sync
     *   Else -> Skip (null)
     *
     * Updated:
     *   If Update API exists -> Update
     *   Else if Sync API exists -> Sync
     *   Else -> Skip (null)
     *
     * Deleted:
     *   If Delete API exists -> Delete
     *   Else -> Skip (null)
     */
    public function resolveMatchingApi(
        DataSyncOutboundConnection $connection,
        string $operation,
        ?string $module = null,
        ?Model $model = null
    ): ?DataSyncOutboundApi {
        $apis = $connection->apis->where('is_active', true);

        // Recognize status_update as a Projects-only operation
        if ($operation === 'status_update') {
            if ($module !== null && $module !== 'projects') {
                return null;
            }

            return $apis->firstWhere('operation', 'status_update');
        }

        if ($operation === 'update') {
            // For projects, if only status_id was changed and a status_update API exists, select it
            if ($module === 'projects' && $model instanceof \App\Models\Project && array_key_exists('status_id', $model->getChanges())) {
                $statusUpdateApi = $apis->firstWhere('operation', 'status_update');
                $changedKeys = array_diff(array_keys($model->getChanges()), ['updated_at', 'updated_by']);
                if ($statusUpdateApi && count($changedKeys) === 1) {
                    return $statusUpdateApi;
                }
            }

            return $apis->firstWhere('operation', 'update')
                ?? $apis->firstWhere('operation', 'sync');
        }

        return match ($operation) {
            'create' => $apis->firstWhere('operation', 'create')
                ?? $apis->firstWhere('operation', 'sync'),

            'delete' => $apis->firstWhere('operation', 'delete'),

            'sync' => $apis->firstWhere('operation', 'sync'),

            default => $apis->firstWhere('operation', $operation),
        };
    }

    /**
     * Retrieve all active, non-soft-deleted connections with active APIs for a module.
     *
     * @return Collection<int, DataSyncOutboundConnection>
     */
    protected function getActiveConnectionsForModule(string $module): Collection
    {
        return DataSyncOutboundConnection::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->with(['apis' => function ($query) use ($module) {
                $query->where('module', $module)
                    ->where('is_active', true);
            }])
            ->get()
            ->filter(fn (DataSyncOutboundConnection $conn) => $conn->apis->isNotEmpty())
            ->values();
    }

    /**
     * Determine if an outbound connection is the originating external system of the change.
     */
    public function isOriginatingExternalSystem(
        DataSyncOutboundConnection $connection,
        ?ApiClient $originatingClient,
        string $module,
        Model $model
    ): bool {
        if (! $originatingClient) {
            return false;
        }

        // 1. Primary check: Explicit foreign key relationship api_client_id
        if ($connection->api_client_id !== null) {
            if ((int) $connection->api_client_id === (int) $originatingClient->id) {
                return true;
            }

            // Connection is explicitly tied to a different API client
            return false;
        }

        // 2. Secondary check: Entity mapping lookup for this specific record
        $entityType = match ($module) {
            'projects' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'customers' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            default => null,
        };

        if ($entityType !== null) {
            $isMappedToClient = DataSyncEntityMapping::query()
                ->where('api_client_id', $originatingClient->id)
                ->where('entity_type', $entityType)
                ->where('local_id', $model->id)
                ->exists();

            if ($isMappedToClient && strcasecmp(trim($connection->name), trim($originatingClient->name)) === 0) {
                return true;
            }
        }

        // 3. Fallback check for backward compatibility: case-insensitive name match
        if (strcasecmp(trim($connection->name), trim($originatingClient->name)) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Recover and re-dispatch pending deliveries stranded due to queue failures.
     *
     * @param  int|null  $olderThanMinutes  Threshold age in minutes (defaults to config: 5 min)
     * @param  int  $limit  Max deliveries to process per run
     * @return int Number of re-dispatched histories
     */
    public function recoverPendingDeliveries(?int $olderThanMinutes = null, int $limit = 50): int
    {
        $minutes = $olderThanMinutes ?? (int) config('data_sync_outbound.recovery_threshold_minutes', 5);
        $cutoff = Carbon::now()->subMinutes($minutes);

        $pendingHistories = DataSyncOutboundHistory::query()
            ->where('status', DataSyncOutboundHistory::STATUS_PENDING)
            ->where('created_at', '<=', $cutoff)
            ->where('updated_at', '<=', $cutoff)
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        $dispatched = 0;
        $queueName = config('data_sync_outbound.queue', 'default');
        $isDatabaseQueue = config('queue.default') === 'database';

        foreach ($pendingHistories as $history) {
            // Verify connection and API are still active and not soft-deleted
            $api = $history->outboundApi;
            $connection = $api?->connection;

            if (! $api || ! $api->is_active || ! $connection || ! $connection->is_active || $connection->trashed()) {
                $history->update([
                    'status' => DataSyncOutboundHistory::STATUS_FAILED,
                    'error_message' => 'Recovery aborted: Outbound connection or API configuration is inactive or deleted.',
                ]);
                continue;
            }

            // In database queue mode, check whether an active job already exists in the jobs table
            if ($isDatabaseQueue && \Illuminate\Support\Facades\Schema::hasTable('jobs')) {
                $alreadyQueued = \Illuminate\Support\Facades\DB::table('jobs')
                    ->where('payload', 'like', '%"historyId":' . $history->id . '%')
                    ->exists();

                if ($alreadyQueued) {
                    continue;
                }
            }

            try {
                // Advance updated_at timestamp to prevent immediate duplicate re-dispatch on subsequent scheduler ticks
                $history->touch();

                SendOutboundApiJob::dispatch($history->id)->afterCommit()->onQueue($queueName);
                $dispatched++;
            } catch (Throwable $e) {
                Log::error("Failed to recover pending delivery [{$history->id}]", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $dispatched;
    }
}


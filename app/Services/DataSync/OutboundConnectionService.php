<?php

namespace App\Services\DataSync;

use App\Models\DataSyncOutboundApi;
use App\Models\DataSyncOutboundConnection;
use App\Models\DataSyncOutboundHistory;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutboundConnectionService
{
    public const AVAILABLE_MODULES = [
        'projects' => 'Projects',
        'customers' => 'Customers',
        'meetings' => 'Meetings',
        'technologies' => 'Technologies',
        'industries' => 'Industries',
        'project_categories' => 'Project Categories',
        'project_statuses' => 'Project Statuses',
    ];

    public const AVAILABLE_OPERATIONS = [
        'create' => 'Create',
        'update' => 'Update',
        'sync' => 'Sync / Upsert',
        'delete' => 'Delete',
    ];

    public const AVAILABLE_METHODS = [
        'POST' => 'POST',
        'GET' => 'GET',
        'PUT' => 'PUT',
        'PATCH' => 'PATCH',
        'DELETE' => 'DELETE',
    ];

    /**
     * Validate destination URL to prevent SSRF.
     * Returns null if valid, or an error message string if invalid.
     */
    public static function validateDestinationUrl(string $url): ?string
    {
        $url = trim($url);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'The base URL must be a valid absolute URL.';
        }

        $parsed = parse_url($url);
        if (! isset($parsed['scheme']) || ! in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            return 'The base URL must use the HTTP or HTTPS protocol.';
        }

        if (empty($parsed['host'])) {
            return 'The base URL must contain a valid hostname.';
        }

        $host = strtolower($parsed['host']);

        // Block loopback, local, internal, and cloud metadata hostnames
        $blockedHosts = [
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
            '169.254.169.254',
            'metadata.google.internal',
            'instance-data',
        ];

        if (
            in_array($host, $blockedHosts, true) ||
            str_ends_with($host, '.localhost') ||
            str_ends_with($host, '.local') ||
            str_ends_with($host, '.internal') ||
            str_ends_with($host, '.lan')
        ) {
            return 'The destination host is restricted and cannot be a local or internal address.';
        }

        // If host is an IP address, block private and reserved ranges
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return 'Internal and private IP addresses are not permitted to prevent SSRF.';
            }
        }

        return null;
    }

    /**
     * Build auth_config array based on auth_type and provided inputs.
     * Only Bearer Token authentication is supported.
     */
    public function buildAuthConfig(string $authType, array $input, ?array $existingConfig = null): array
    {
        if ($authType !== DataSyncOutboundConnection::AUTH_TYPE_BEARER) {
            throw new \InvalidArgumentException("Unsupported authentication type [{$authType}]. Only Bearer Token authentication is supported.");
        }

        $token = ! empty($input['token']) ? trim($input['token']) : ($existingConfig['token'] ?? '');

        return [
            'token' => $token,
        ];
    }

    /**
     * Create a new outbound connection.
     */
    public function createConnection(array $data): DataSyncOutboundConnection
    {
        $authType = DataSyncOutboundConnection::AUTH_TYPE_BEARER;
        $authConfig = $this->buildAuthConfig($authType, $data);

        return DataSyncOutboundConnection::create([
            'name' => trim($data['name']),
            'base_url' => rtrim(trim($data['base_url']), '/'),
            'auth_type' => $authType,
            'auth_config' => $authConfig,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);
    }

    /**
     * Update an existing outbound connection.
     */
    public function updateConnection(DataSyncOutboundConnection $connection, array $data): DataSyncOutboundConnection
    {
        $authType = DataSyncOutboundConnection::AUTH_TYPE_BEARER;
        $existingConfig = $connection->auth_config;
        $authConfig = $this->buildAuthConfig($authType, $data, $existingConfig);

        $connection->update([
            'name' => trim($data['name']),
            'base_url' => rtrim(trim($data['base_url']), '/'),
            'auth_type' => $authType,
            'auth_config' => $authConfig,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $connection->is_active,
        ]);

        return $connection;
    }

    /**
     * Delete an outbound connection (soft delete).
     */
    public function deleteConnection(DataSyncOutboundConnection $connection): bool
    {
        return (bool) $connection->delete();
    }

    /**
     * Toggle active status of an outbound connection.
     */
    public function toggleConnectionStatus(DataSyncOutboundConnection $connection): bool
    {
        $newStatus = ! (bool) $connection->is_active;
        $connection->update(['is_active' => $newStatus]);

        return $newStatus;
    }

    /**
     * Check for conflicting operations for a given connection and module.
     * Returns null if valid, or a validation error message string if conflicting.
     */
    public static function getOperationConflictError(
        int $connectionId,
        string $module,
        string $operation,
        ?int $ignoreApiId = null,
        bool $lock = false
    ): ?string {
        $module = trim($module);
        $operation = trim($operation);

        $query = DataSyncOutboundApi::query()
            ->where('connection_id', $connectionId)
            ->where('module', $module);

        if ($ignoreApiId !== null) {
            $query->where('id', '!=', $ignoreApiId);
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        $existingOperations = $query->pluck('operation')->all();

        // 1. Duplicate check
        if (in_array($operation, $existingOperations, true)) {
            return 'This operation is already configured for the selected module.';
        }

        // 2. If 'sync' already exists, 'create' and 'update' are disallowed
        if (in_array('sync', $existingOperations, true) && in_array($operation, ['create', 'update'], true)) {
            return 'Sync / Upsert is already configured for this module. Create and Update operations cannot be added.';
        }

        // 3. If 'create' or 'update' already exists, 'sync' is disallowed
        if ($operation === 'sync' && (in_array('create', $existingOperations, true) || in_array('update', $existingOperations, true))) {
            return 'Create or Update is already configured for this module. Sync / Upsert cannot be added.';
        }

        return null;
    }

    /**
     * Create an API configuration for a connection.
     */
    public function createApi(DataSyncOutboundConnection $connection, array $data): DataSyncOutboundApi
    {
        return DB::transaction(function () use ($connection, $data) {
            // Lock connection record to prevent concurrent creation conflicts for the same connection
            DataSyncOutboundConnection::query()->where('id', $connection->id)->lockForUpdate()->first();

            $module = trim($data['module']);
            $operation = trim($data['operation']);

            $conflictError = self::getOperationConflictError($connection->id, $module, $operation, null, true);
            if ($conflictError) {
                throw ValidationException::withMessages([
                    'operation' => [$conflictError],
                ]);
            }

            $endpoint = trim($data['endpoint']);
            if (! str_starts_with($endpoint, '/')) {
                $endpoint = '/' . $endpoint;
            }

            return $connection->apis()->create([
                'module' => $module,
                'operation' => $operation,
                'method' => strtoupper(trim($data['method'])),
                'endpoint' => $endpoint,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ]);
        });
    }

    /**
     * Update an API configuration.
     */
    public function updateApi(DataSyncOutboundApi $api, array $data): DataSyncOutboundApi
    {
        return DB::transaction(function () use ($api, $data) {
            // Lock connection record to prevent concurrent update conflicts for the same connection
            DataSyncOutboundConnection::query()->where('id', $api->connection_id)->lockForUpdate()->first();

            $module = trim($data['module']);
            $operation = trim($data['operation']);

            $conflictError = self::getOperationConflictError($api->connection_id, $module, $operation, $api->id, true);
            if ($conflictError) {
                throw ValidationException::withMessages([
                    'operation' => [$conflictError],
                ]);
            }

            $endpoint = trim($data['endpoint']);
            if (! str_starts_with($endpoint, '/')) {
                $endpoint = '/' . $endpoint;
            }

            $api->update([
                'module' => $module,
                'operation' => $operation,
                'method' => strtoupper(trim($data['method'])),
                'endpoint' => $endpoint,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $api->is_active,
            ]);

            return $api;
        });
    }

    /**
     * Delete an API configuration.
     */
    public function deleteApi(DataSyncOutboundApi $api): bool
    {
        return (bool) $api->delete();
    }

    /**
     * Toggle active status of an API configuration.
     */
    public function toggleApiStatus(DataSyncOutboundApi $api): bool
    {
        $newStatus = ! (bool) $api->is_active;
        $api->update(['is_active' => $newStatus]);

        return $newStatus;
    }

    /**
     * Query outbound history records with filtering, search, and pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getHistories(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataSyncOutboundHistory::with(['outboundApi.connection'])->latest('id');

        if (! empty($filters['connection_id'])) {
            $query->whereHas('outboundApi', function ($q) use ($filters) {
                $q->where('connection_id', $filters['connection_id']);
            });
        }

        if (! empty($filters['module'])) {
            $query->whereHas('outboundApi', function ($q) use ($filters) {
                $q->where('module', $filters['module']);
            });
        }

        if (! empty($filters['operation'])) {
            $query->whereHas('outboundApi', function ($q) use ($filters) {
                $q->where('operation', $filters['operation']);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $dateFrom = $filters['date_from'] ?? $filters['start_date'] ?? null;
        $dateTo = $filters['date_to'] ?? $filters['end_date'] ?? null;

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('request_url', 'like', "%{$search}%")
                    ->orWhere('response_body', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Sanitize and redact sensitive credentials from payload data.
     */
    public function sanitizePayload(mixed $data): mixed
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->sanitizePayload($decoded);
            }

            return $data;
        }

        if (! is_array($data)) {
            return $data;
        }

        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'refresh_token',
            'api_key',
            'apikey',
            'secret',
            'client_secret',
            'authorization',
            'auth_config',
            'credential',
            'credentials',
            'private_key',
        ];

        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $pattern) {
                if ($lowerKey === $pattern || str_contains($lowerKey, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Redact sensitive query parameters from a request URL.
     */
    public function sanitizeUrl(string $url): string
    {
        return preg_replace('/([?&](?:token|api_key|apikey|key|secret|password|access_token)=)[^&]*/i', '$1***REDACTED***', $url);
    }

    /**
     * Format an outbound history record for detailed view inspection.
     *
     * @return array<string, mixed>
     */
    public function formatHistoryDetails(DataSyncOutboundHistory $history): array
    {
        $history->loadMissing(['outboundApi.connection']);

        $api = $history->outboundApi;
        $connection = $api?->connection;

        $sanitizedPayload = $history->request_payload !== null
            ? $this->sanitizePayload($history->request_payload)
            : null;

        $formattedPayload = $sanitizedPayload !== null
            ? json_encode($sanitizedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : null;

        $formattedResponseBody = $history->response_body;
        if (! empty($formattedResponseBody)) {
            $decodedResponse = json_decode($formattedResponseBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
                $formattedResponseBody = json_encode($this->sanitizePayload($decodedResponse), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        }

        return [
            'id' => $history->id,
            'connection_name' => $connection?->name ?? '—',
            'module' => $api ? (self::AVAILABLE_MODULES[$api->module] ?? ucfirst($api->module)) : '—',
            'operation' => $api ? (self::AVAILABLE_OPERATIONS[$api->operation] ?? ucfirst($api->operation)) : '—',
            'http_method' => $api?->method ?? '—',
            'endpoint' => $api?->endpoint ?? '—',
            'request_url' => $this->sanitizeUrl($history->request_url ?? '—'),
            'request_payload' => $formattedPayload,
            'response_code' => $history->response_code,
            'response_body' => $formattedResponseBody,
            'status' => $history->status,
            'error_message' => $history->error_message,
            'created_at' => $history->created_at?->format('M d, Y H:i:s') ?? '—',
        ];
    }
}

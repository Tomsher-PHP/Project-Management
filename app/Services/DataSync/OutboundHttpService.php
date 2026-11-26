<?php

namespace App\Services\DataSync;

use App\Models\Customer;
use App\Models\DataSyncEntityMapping;
use App\Models\DataSyncOutboundApi;
use App\Models\DataSyncOutboundConnection;
use App\Models\DataSyncOutboundHistory;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class OutboundHttpService
{
    /**
     * Supported HTTP methods for outbound API calls.
     */
    public const SUPPORTED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Resolve the full destination URL, replacing placeholders like {id} and {code}.
     *
     * @param  DataSyncOutboundConnection  $connection
     * @param  DataSyncOutboundApi  $api
     * @param  Model  $model
     * @return string
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function buildUrl(
        DataSyncOutboundConnection $connection,
        DataSyncOutboundApi $api,
        Model $model
    ): string {
        $baseUrl = rtrim(trim($connection->base_url), '/');
        $endpoint = trim($api->endpoint);

        // Resolve {id} placeholder if present
        if (str_contains($endpoint, '{id}')) {
            $resolvedId = $this->resolveExternalIdentifier($api->module, $model, $connection);
            if ($resolvedId === null || trim((string) $resolvedId) === '') {
                throw new RuntimeException(
                    "Cannot resolve external identifier for endpoint placeholder {id} on module [{$api->module}]. " .
                    "Direct PMS database ID substitution is strictly forbidden to preserve integration safety."
                );
            }

            $endpoint = str_replace('{id}', rawurlencode((string) $resolvedId), $endpoint);
        }

        // Resolve {code} placeholder if present
        if (str_contains($endpoint, '{code}')) {
            $resolvedCode = $this->resolveEntityCode($api->module, $model);
            if ($resolvedCode === null || trim((string) $resolvedCode) === '') {
                throw new RuntimeException("Cannot resolve entity code for endpoint placeholder {code} on module [{$api->module}].");
            }

            $endpoint = str_replace('{code}', rawurlencode((string) $resolvedCode), $endpoint);
        }

        $fullUrl = $baseUrl . '/' . ltrim($endpoint, '/');

        // Validate the full constructed URL against SSRF and protocol policies
        $this->validateDestinationUrl($fullUrl);

        return $fullUrl;
    }

    /**
     * Validate destination URL for SSRF prevention, host safety, and HTTPS enforcement.
     *
     * @param  string  $url
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function validateDestinationUrl(string $url): void
    {
        $url = trim($url);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Destination URL [{$url}] is not a valid absolute URL.");
        }

        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');

        $requireHttps = config('data_sync_outbound.require_https', true);
        if ($requireHttps && $scheme !== 'https') {
            throw new InvalidArgumentException("Outbound synchronization requires HTTPS protocol. [{$scheme}] is not permitted.");
        }

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("Outbound destination URL must use HTTP or HTTPS protocol.");
        }

        if (empty($parsed['host'])) {
            throw new InvalidArgumentException("Destination URL missing a valid host.");
        }

        $host = strtolower($parsed['host']);

        // Block loopback, link-local, private, and cloud metadata hostnames
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
            throw new InvalidArgumentException("Destination host [{$host}] is restricted to prevent SSRF.");
        }

        // If host is an IP address, block private and reserved ranges
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException("Destination IP [{$host}] belongs to private/reserved range and is restricted to prevent SSRF.");
            }
        }
    }

    /**
     * Send outbound HTTP request for a delivery history record.
     *
     * @param  DataSyncOutboundHistory  $history
     * @return array{
     *     success: bool,
     *     status_code: int|null,
     *     body: string|null,
     *     error: string|null,
     *     is_retryable: bool
     * }
     */
    public function send(DataSyncOutboundHistory $history): array
    {
        $api = $history->outboundApi;
        if (! $api) {
            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => 'Associated Outbound API configuration was not found.',
                'is_retryable' => false,
            ];
        }

        $connection = $api->connection;
        if (! $connection) {
            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => 'Associated Outbound Connection was not found.',
                'is_retryable' => false,
            ];
        }

        // Decrypt Bearer Token from encrypted model attribute
        $token = $this->extractBearerToken($connection);
        if ($token === null || trim($token) === '') {
            return [
                'success' => false,
                'status_code' => 401,
                'body' => null,
                'error' => 'Missing or unconfigured Bearer Token in outbound connection credentials.',
                'is_retryable' => false,
            ];
        }

        $url = $history->request_url;
        try {
            $this->validateDestinationUrl($url);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => 'SSRF Validation Error: ' . $e->getMessage(),
                'is_retryable' => false,
            ];
        }

        $method = strtoupper(trim($api->method));
        if (! in_array($method, self::SUPPORTED_METHODS, true)) {
            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => "Unsupported HTTP method [{$method}].",
                'is_retryable' => false,
            ];
        }

        $timeout = config('data_sync_outbound.timeout', 30);
        $payload = $history->request_payload ?? [];

        try {
            $client = Http::withToken($token)
                ->timeout($timeout)
                ->withoutRedirecting()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]);

            /** @var Response $response */
            if (in_array($method, ['GET', 'DELETE'], true) && empty($payload)) {
                $response = $client->send($method, $url);
            } else {
                $response = $client->send($method, $url, [
                    'json' => $payload,
                ]);
            }

            $statusCode = $response->status();
            $body = $response->body();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'body' => $body,
                    'error' => null,
                    'is_retryable' => false,
                ];
            }

            $isRetryable = $this->isRetryableStatusCode($statusCode);
            $errorMessage = "External API returned HTTP {$statusCode}: " . ($body ? substr($body, 0, 500) : 'Empty response body');

            return [
                'success' => false,
                'status_code' => $statusCode,
                'body' => $body,
                'error' => $errorMessage,
                'is_retryable' => $isRetryable,
            ];
        } catch (ConnectionException $e) {
            // Connection timeouts and network drops are temporary / retryable
            Log::warning('Outbound Data Sync connection exception', [
                'history_id' => $history->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => 'Connection timeout or network failure: ' . $e->getMessage(),
                'is_retryable' => true,
            ];
        } catch (Throwable $e) {
            Log::error('Outbound Data Sync unexpected HTTP failure', [
                'history_id' => $history->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => 'Unexpected error: ' . $e->getMessage(),
                'is_retryable' => false,
            ];
        }
    }

    /**
     * Decrypt and retrieve the Bearer Token from the connection.
     */
    protected function extractBearerToken(DataSyncOutboundConnection $connection): ?string
    {
        $authConfig = $connection->auth_config;
        if (! is_array($authConfig)) {
            return null;
        }

        return ! empty($authConfig['token']) ? (string) $authConfig['token'] : null;
    }

    /**
     * Determine whether an HTTP status code qualifies for automatic retry.
     */
    public function isRetryableStatusCode(int $statusCode): bool
    {
        $retryableCodes = config('data_sync_outbound.retry.retryable_status_codes', [429, 500, 502, 503, 504]);

        return in_array($statusCode, $retryableCodes, true);
    }

    /**
     * Resolve the external identifier for an endpoint placeholder {id}.
     * Note: Does NOT automatically substitute PMS database auto-increment ID.
     */
    protected function resolveExternalIdentifier(
        string $module,
        Model $model,
        DataSyncOutboundConnection $connection
    ): ?string {
        // Settings masters contractually identify by their code
        if (in_array($module, ['technologies', 'industries', 'project_categories', 'project_statuses'], true)) {
            return ! empty($model->code) ? (string) $model->code : null;
        }

        // Projects and Customers check DataSyncEntityMapping
        $entityType = match ($module) {
            'projects' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'customers' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            default => null,
        };

        if ($entityType !== null) {
            $mappingQuery = DataSyncEntityMapping::query()
                ->where('entity_type', $entityType)
                ->where('local_id', $model->id);

            // Prioritize mapping for client matching connection name
            $matchingClient = \App\Models\ApiClient::where('name', $connection->name)->first();
            if ($matchingClient) {
                $clientMapping = (clone $mappingQuery)->where('api_client_id', $matchingClient->id)->first();
                if ($clientMapping) {
                    return $clientMapping->external_id ?: $clientMapping->external_code;
                }
            }

            $genericMapping = $mappingQuery->first();
            if ($genericMapping) {
                return $genericMapping->external_id ?: $genericMapping->external_code;
            }

            // If no external mapping exists, we strictly do NOT fallback to internal PMS database ID.
            return null;
        }

        // Meetings: return meeting external ID or null if unmapped
        return null;
    }

    /**
     * Resolve the entity code for an endpoint placeholder {code}.
     */
    protected function resolveEntityCode(string $module, Model $model): ?string
    {
        return match ($module) {
            'projects' => ! empty($model->project_code) ? (string) $model->project_code : null,
            'customers' => ! empty($model->customer_code) ? (string) $model->customer_code : null,
            'technologies', 'industries', 'project_categories', 'project_statuses' => ! empty($model->code) ? (string) $model->code : null,
            default => null,
        };
    }
}


<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\StoreApiClientRequest;
use App\Http\Requests\Settings\StoreOutboundApiRequest;
use App\Http\Requests\Settings\StoreOutboundConnectionRequest;
use App\Http\Requests\Settings\UpdateApiClientRequest;
use App\Http\Requests\Settings\UpdateOutboundApiRequest;
use App\Http\Requests\Settings\UpdateOutboundConnectionRequest;
use App\Models\ApiClient;
use App\Models\DataSyncOutboundApi;
use App\Models\DataSyncOutboundConnection;
use App\Models\DataSyncOutboundHistory;
use App\Services\ApiClientService;
use App\Services\DataSync\Explorer\DataSyncExplorerRegistry;
use App\Services\DataSync\OutboundConnectionService;
use App\Services\DataSync\RecordMappingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataSyncSettingsController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Data Sync Settings';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    /**
     * Display the Data Sync Settings index (defaults to API Clients tab).
     */
    public function index(Request $request): View
    {
        return $this->clients($request);
    }

    /**
     * Display the API Clients tab with filtered and paginated listing.
     */
    public function clients(Request $request): View
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 15));
        $perPage = max(5, min(500, $perPage));

        $query = ApiClient::query();

        // Search by application name or public key_id
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key_id', 'like', "%{$search}%");
            });
        }

        // Filter by status (active, inactive, expired)
        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'expired') {
            $query->whereNotNull('expires_at')
                ->where('expires_at', '<=', now());
        }

        $clients = $query->sort($request->all())->paginate($perPage)->withQueryString();

        return view('settings.data-sync.index', [
            'currentSection' => 'inbound',
            'currentTab' => 'clients',
            'clients' => $clients,
            'perPage' => $perPage,
            'supportedScopes' => ApiClientService::SUPPORTED_SCOPES,
        ]);
    }

    /**
     * Return detailed client data for editing via modal.
     */
    public function showClient(ApiClient $client): JsonResponse
    {
        return response()->json([
            'success' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'key_id' => $client->key_id,
                'scopes' => is_array($client->scopes) ? $client->scopes : [],
                'is_active' => (bool) $client->is_active,
                'expires_at' => $client->expires_at ? $client->expires_at->format('Y-m-d') : null,
                'has_expiration' => $client->expires_at !== null,
                'created_at' => $client->created_at?->format('M d, Y H:i:s'),
                'last_used_at' => $client->last_used_at?->format('M d, Y H:i:s') ?? 'Never',
            ],
        ]);
    }

    /**
     * Generate a new API Client and return the one-time raw secret token.
     */
    public function storeClient(StoreApiClientRequest $request, ApiClientService $clientService): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $expiresAt = $validated['expiration_type'] === 'never' ? null : ($validated['expires_at'] ?? null);

        try {
            $result = $clientService->createClient(
                name: $validated['name'],
                scopes: $validated['scopes'],
                expiresAt: $expiresAt,
            );

            $client = $result['client'];
            $plainTextKey = $result['plain_text_key'];

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'API client created successfully.',
                    'plain_text_key' => $plainTextKey,
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'key_id' => $client->key_id,
                        'scopes' => $client->scopes ?? [],
                        'expires_at' => $client->expires_at ? $client->expires_at->format('Y-m-d') : 'Never',
                    ],
                ], 201);
            }

            return redirect()->route('settings.data-sync.clients')->with('new_api_key', [
                'name' => $client->name,
                'key' => $plainTextKey,
                'scopes' => $client->scopes ?? [],
                'expires_at' => $client->expires_at ? $client->expires_at->format('Y-m-d') : 'Never',
            ]);
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withErrors(['name' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Update an existing API Client's name, scopes, and expiration.
     */
    public function updateClient(UpdateApiClientRequest $request, ApiClient $client): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $expiresAt = $validated['expiration_type'] === 'never' ? null : Carbon::parse($validated['expires_at']);

        $client->update([
            'name' => trim($validated['name']),
            'scopes' => array_values(array_unique($validated['scopes'])),
            'expires_at' => $expiresAt,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "API client [{$client->name}] updated successfully.",
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'key_id' => $client->key_id,
                    'scopes' => $client->scopes,
                    'expires_at' => $client->expires_at ? $client->expires_at->format('Y-m-d') : 'Never',
                ],
            ]);
        }

        return redirect()->route('settings.data-sync.clients')->with('success', "API client [{$client->name}] updated successfully.");
    }

    /**
     * Toggle the active status of an API client.
     */
    public function toggleStatus(ApiClient $client): JsonResponse
    {
        $newStatus = ! (bool) $client->is_active;

        $client->update([
            'is_active' => $newStatus,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $newStatus,
            'status' => $newStatus ? 1 : 0,
            'message' => "API client [{$client->name}] has been " . ($newStatus ? 'activated' : 'deactivated') . ' successfully.',
        ]);
    }

    /**
     * Rotate the API key secret for an existing API client.
     */
    public function rotateKey(ApiClient $client, ApiClientService $clientService): JsonResponse|RedirectResponse
    {
        try {
            $result = $clientService->rotateClient($client->key_id);
            $plainTextKey = $result['plain_text_key'];

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "API key for [{$client->name}] rotated successfully.",
                    'plain_text_key' => $plainTextKey,
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'key_id' => $client->key_id,
                        'scopes' => $client->scopes ?? [],
                        'expires_at' => $client->expires_at ? $client->expires_at->format('Y-m-d') : 'Never',
                    ],
                ]);
            }

            return redirect()->route('settings.data-sync.clients')->with('rotated_key', [
                'name' => $client->name,
                'key' => $plainTextKey,
                'scopes' => $client->scopes ?? [],
                'expires_at' => $client->expires_at ? $client->expires_at->format('Y-m-d') : 'Never',
            ]);
        } catch (\InvalidArgumentException $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Revoke an active API client by setting is_active = false.
     * Preserves entity mappings and historical records without physical deletion.
     */
    public function revokeClient(ApiClient $client, ApiClientService $clientService): JsonResponse|RedirectResponse
    {
        try {
            $clientService->revokeClient($client->key_id);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "API client [{$client->name}] has been revoked successfully.",
                ]);
            }

            return redirect()->route('settings.data-sync.clients')->with('success', "API client [{$client->name}] has been revoked successfully.");
        } catch (\InvalidArgumentException $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the API Explorer tab.
     */
    public function explorer(DataSyncExplorerRegistry $registry): View
    {
        $explorerData = $registry->getAllData(url('/'));

        return view('settings.data-sync.index', [
            'currentSection' => 'inbound',
            'currentTab' => 'explorer',
            'explorerData' => $explorerData,
        ]);
    }

    /**
     * Display the Record Mappings tab with filtered and paginated listing.
     */
    public function mappings(Request $request, RecordMappingService $mappingService): View
    {
        $perPage = (int) $request->input('per_page', RecordMappingService::DEFAULT_PER_PAGE);
        $filters = [
            'api_client_id' => $request->input('api_client_id'),
            'entity_type' => $request->input('entity_type'),
            'search' => $request->input('search'),
        ];

        $mappings = $mappingService->getMappings($filters, $perPage);
        $apiClients = $mappingService->getApiClientFilterOptions();
        $entityTypes = $mappingService->getEntityTypeOptions();

        return view('settings.data-sync.index', [
            'currentSection' => 'inbound',
            'currentTab' => 'mappings',
            'mappings' => $mappings,
            'apiClients' => $apiClients,
            'entityTypes' => $entityTypes,
            'filters' => $filters,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Display the Outbound Settings index (defaults to Connections tab).
     */
    public function outbound(Request $request): View
    {
        return $this->outboundConnections($request);
    }

    /**
     * Display the Outbound Connections tab.
     */
    public function outboundConnections(Request $request): View
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 15));
        $perPage = max(5, min(500, $perPage));

        $query = DataSyncOutboundConnection::query()->withCount('apis');

        // Search by connection name or base URL
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('base_url', 'like', "%{$search}%");
            });
        }

        // Filter by status (active / inactive)
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by auth type
        if ($request->filled('auth_type')) {
            $query->where('auth_type', $request->input('auth_type'));
        }

        $connections = $query->latest('id')->paginate($perPage)->withQueryString();

        return view('settings.data-sync.outbound.index', [
            'currentSection' => 'outbound',
            'currentTab' => 'connections',
            'connections' => $connections,
            'perPage' => $perPage,
            'supportedAuthTypes' => [
                DataSyncOutboundConnection::AUTH_TYPE_BEARER => 'Bearer Token',
                DataSyncOutboundConnection::AUTH_TYPE_API_KEY => 'API Key',
                DataSyncOutboundConnection::AUTH_TYPE_BASIC => 'Basic Authentication',
                DataSyncOutboundConnection::AUTH_TYPE_NONE => 'None',
            ],
        ]);
    }

    /**
     * Show an Outbound Connection's data (sanitized, no plaintext credentials).
     */
    public function showConnection(DataSyncOutboundConnection $connection): JsonResponse
    {
        return response()->json([
            'success' => true,
            'connection' => [
                'id' => $connection->id,
                'name' => $connection->name,
                'base_url' => $connection->base_url,
                'auth_type' => $connection->auth_type,
                'is_active' => (bool) $connection->is_active,
                'has_credentials' => ! empty($connection->auth_config),
                'header_name' => $connection->auth_config['header_name'] ?? 'X-API-Key',
                'username' => $connection->auth_config['username'] ?? '',
                'apis_count' => $connection->apis()->count(),
                'created_at' => $connection->created_at?->format('M d, Y H:i:s'),
            ],
        ]);
    }

    /**
     * Display the Outbound Connection edit/details page with configured APIs.
     */
    public function editConnection(DataSyncOutboundConnection $connection): View
    {
        $apis = $connection->apis()->latest('id')->get();

        return view('settings.data-sync.outbound.edit', [
            'currentSection' => 'outbound',
            'currentTab' => 'connections',
            'connection' => $connection,
            'apis' => $apis,
            'supportedAuthTypes' => [
                DataSyncOutboundConnection::AUTH_TYPE_BEARER => 'Bearer Token',
                DataSyncOutboundConnection::AUTH_TYPE_API_KEY => 'API Key',
                DataSyncOutboundConnection::AUTH_TYPE_BASIC => 'Basic Authentication',
                DataSyncOutboundConnection::AUTH_TYPE_NONE => 'None',
            ],
            'availableModules' => OutboundConnectionService::AVAILABLE_MODULES,
            'availableOperations' => OutboundConnectionService::AVAILABLE_OPERATIONS,
            'availableMethods' => OutboundConnectionService::AVAILABLE_METHODS,
        ]);
    }

    /**
     * Store a newly created Outbound Connection.
     */
    public function storeConnection(StoreOutboundConnectionRequest $request, OutboundConnectionService $service): JsonResponse|RedirectResponse
    {
        $connection = $service->createConnection($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Outbound connection [{$connection->name}] created successfully.",
                'connection' => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'base_url' => $connection->base_url,
                    'auth_type' => $connection->auth_type,
                    'is_active' => (bool) $connection->is_active,
                ],
                'redirect_url' => route('settings.data-sync.outbound.connections.edit', $connection),
            ], 201);
        }

        return redirect()->route('settings.data-sync.outbound.connections.edit', $connection)
            ->with('success', "Outbound connection [{$connection->name}] created successfully.");
    }

    /**
     * Update an existing Outbound Connection.
     */
    public function updateConnection(UpdateOutboundConnectionRequest $request, DataSyncOutboundConnection $connection, OutboundConnectionService $service): JsonResponse|RedirectResponse
    {
        $service->updateConnection($connection, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Outbound connection [{$connection->name}] updated successfully.",
                'connection' => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'base_url' => $connection->base_url,
                    'auth_type' => $connection->auth_type,
                    'is_active' => (bool) $connection->is_active,
                ],
            ]);
        }

        return redirect()->route('settings.data-sync.outbound.connections.edit', $connection)
            ->with('success', "Outbound connection [{$connection->name}] updated successfully.");
    }

    /**
     * Delete an Outbound Connection (soft delete).
     */
    public function destroyConnection(DataSyncOutboundConnection $connection, OutboundConnectionService $service): JsonResponse|RedirectResponse
    {
        $service->deleteConnection($connection);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Outbound connection [{$connection->name}] deleted successfully.",
            ]);
        }

        return redirect()->route('settings.data-sync.outbound.connections')
            ->with('success', "Outbound connection [{$connection->name}] deleted successfully.");
    }

    /**
     * Toggle the active status of an Outbound Connection.
     */
    public function toggleConnectionStatus(DataSyncOutboundConnection $connection, OutboundConnectionService $service): JsonResponse
    {
        $newStatus = $service->toggleConnectionStatus($connection);

        return response()->json([
            'success' => true,
            'is_active' => $newStatus,
            'status' => $newStatus ? 1 : 0,
            'message' => "Outbound connection [{$connection->name}] has been " . ($newStatus ? 'activated' : 'deactivated') . ' successfully.',
        ]);
    }

    /**
     * Store a newly created Outbound API configuration.
     */
    public function storeApi(StoreOutboundApiRequest $request, DataSyncOutboundConnection $connection, OutboundConnectionService $service): JsonResponse|RedirectResponse
    {
        $api = $service->createApi($connection, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "API configuration [{$api->module} - {$api->operation}] created successfully.",
                'api' => [
                    'id' => $api->id,
                    'connection_id' => $api->connection_id,
                    'module' => $api->module,
                    'operation' => $api->operation,
                    'method' => $api->method,
                    'endpoint' => $api->endpoint,
                    'is_active' => (bool) $api->is_active,
                ],
            ], 201);
        }

        return redirect()->route('settings.data-sync.outbound.connections.edit', $connection)
            ->with('success', "API configuration [{$api->module} - {$api->operation}] created successfully.");
    }

    /**
     * Show an Outbound API configuration.
     */
    public function showApi(DataSyncOutboundConnection $connection, DataSyncOutboundApi $api): JsonResponse
    {
        return response()->json([
            'success' => true,
            'api' => [
                'id' => $api->id,
                'connection_id' => $api->connection_id,
                'module' => $api->module,
                'operation' => $api->operation,
                'method' => $api->method,
                'endpoint' => $api->endpoint,
                'is_active' => (bool) $api->is_active,
            ],
        ]);
    }

    /**
     * Update an existing Outbound API configuration.
     */
    public function updateApi(UpdateOutboundApiRequest $request, DataSyncOutboundConnection $connection, DataSyncOutboundApi $api, OutboundConnectionService $service): JsonResponse|RedirectResponse
    {
        $service->updateApi($api, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "API configuration [{$api->module} - {$api->operation}] updated successfully.",
                'api' => [
                    'id' => $api->id,
                    'connection_id' => $api->connection_id,
                    'module' => $api->module,
                    'operation' => $api->operation,
                    'method' => $api->method,
                    'endpoint' => $api->endpoint,
                    'is_active' => (bool) $api->is_active,
                ],
            ]);
        }

        return redirect()->route('settings.data-sync.outbound.connections.edit', $connection)
            ->with('success', "API configuration [{$api->module} - {$api->operation}] updated successfully.");
    }

    /**
     * Delete an Outbound API configuration.
     */
    public function destroyApi(DataSyncOutboundConnection $connection, DataSyncOutboundApi $api, OutboundConnectionService $service): JsonResponse|RedirectResponse
    {
        $service->deleteApi($api);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "API configuration [{$api->module} - {$api->operation}] deleted successfully.",
            ]);
        }

        return redirect()->route('settings.data-sync.outbound.connections.edit', $connection)
            ->with('success', "API configuration [{$api->module} - {$api->operation}] deleted successfully.");
    }

    /**
     * Toggle the active status of an Outbound API configuration.
     */
    public function toggleApiStatus(DataSyncOutboundConnection $connection, DataSyncOutboundApi $api, OutboundConnectionService $service): JsonResponse
    {
        $newStatus = $service->toggleApiStatus($api);

        return response()->json([
            'success' => true,
            'is_active' => $newStatus,
            'status' => $newStatus ? 1 : 0,
            'message' => "API configuration [{$api->module} - {$api->operation}] has been " . ($newStatus ? 'activated' : 'deactivated') . ' successfully.',
        ]);
    }

    /**
     * Display the Outbound History tab.
     */
    public function outboundHistory(Request $request, OutboundConnectionService $service): View
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 15));
        $perPage = max(5, min(500, $perPage));

        $histories = $service->getHistories($request->all(), $perPage);
        $connections = DataSyncOutboundConnection::select('id', 'name')->orderBy('name')->get();

        return view('settings.data-sync.outbound.index', [
            'currentSection' => 'outbound',
            'currentTab' => 'history',
            'histories' => $histories,
            'connections' => $connections,
            'modules' => OutboundConnectionService::AVAILABLE_MODULES,
            'operations' => OutboundConnectionService::AVAILABLE_OPERATIONS,
            'statuses' => [
                DataSyncOutboundHistory::STATUS_PENDING => 'Pending',
                DataSyncOutboundHistory::STATUS_SUCCESS => 'Success',
                DataSyncOutboundHistory::STATUS_FAILED => 'Failed',
            ],
            'perPage' => $perPage,
        ]);
    }

    /**
     * Return detailed history data for modal inspection via AJAX.
     */
    public function showHistory(DataSyncOutboundHistory $history, OutboundConnectionService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'history' => $service->formatHistoryDetails($history),
        ]);
    }

    /**
     * Render the Data Sync Settings view with the active tab.
     */
    protected function renderView(string $currentTab): View
    {
        return view('settings.data-sync.index', [
            'currentSection' => 'inbound',
            'currentTab' => $currentTab,
        ]);
    }
}

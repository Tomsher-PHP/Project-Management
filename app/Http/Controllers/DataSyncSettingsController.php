<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\StoreApiClientRequest;
use App\Http\Requests\Settings\UpdateApiClientRequest;
use App\Models\ApiClient;
use App\Services\ApiClientService;
use App\Services\DataSync\Explorer\DataSyncExplorerRegistry;
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
        $this->pageTitle = 'Data Sync';
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
            'currentTab' => 'mappings',
            'mappings' => $mappings,
            'apiClients' => $apiClients,
            'entityTypes' => $entityTypes,
            'filters' => $filters,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Render the Data Sync Settings view with the active tab.
     */
    protected function renderView(string $currentTab): View
    {
        return view('settings.data-sync.index', [
            'currentTab' => $currentTab,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\DataSync;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataSync\SettingsResource;
use App\Models\ApiClient;
use App\Services\DataSync\Settings\ModuleResolver;
use App\Services\DataSync\Settings\SettingsService;
use BadMethodCallException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SettingsController extends Controller
{
    public function __construct(
        protected ModuleResolver $moduleResolver,
        protected SettingsService $settingsService
    ) {}

    public function index(Request $request, string $module): JsonResponse
    {
        if ($errorResponse = $this->authorizeAndValidateModule($request, $module, ApiClient::SCOPE_SETTINGS_READ)) {
            return $errorResponse;
        }

        $validator = Validator::make($request->query(), [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'sort_by' => ['sometimes', 'string', Rule::in($this->settingsService->allowedSortColumns($module))],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $paginator = $this->settingsService->list($module, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Records retrieved successfully.',
            'data' => SettingsResource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $module, string $code): JsonResponse
    {
        if ($errorResponse = $this->authorizeAndValidateModule($request, $module, ApiClient::SCOPE_SETTINGS_READ)) {
            return $errorResponse;
        }

        try {
            $record = $this->settingsService->find($module, $code);

            return response()->json([
                'success' => true,
                'message' => 'Record retrieved successfully.',
                'data' => (new SettingsResource($record))->resolve($request),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        }
    }

    public function store(Request $request, string $module): JsonResponse
    {
        if ($errorResponse = $this->authorizeAndValidateModule($request, $module, ApiClient::SCOPE_SETTINGS_WRITE)) {
            return $errorResponse;
        }

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        $cacheKey = null;
        $payloadHash = null;

        if ($idempotencyKey !== '') {
            /** @var ApiClient $apiClient */
            $apiClient = $request->attributes->get('api_client');
            $cacheKey = sprintf('data-sync:idempotency:%d:%s:%s', $apiClient->id, $module, sha1($idempotencyKey));
            $payloadHash = sha1(json_encode($request->all()));

            if ($cached = Cache::get($cacheKey)) {
                if (($cached['payload_hash'] ?? null) !== $payloadHash) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Idempotency-Key reuse with different request payload.',
                    ], 409);
                }

                return response()->json($cached['body'], 201);
            }
        }

        try {
            $record = $this->settingsService->create($module, $request->all());

            $body = [
                'success' => true,
                'message' => 'Record created successfully.',
                'data' => (new SettingsResource($record))->resolve($request),
            ];

            if ($cacheKey !== null) {
                Cache::put($cacheKey, [
                    'payload_hash' => $payloadHash,
                    'body' => $body,
                ], now()->addMinutes(10));
            }

            return response()->json($body, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (ConflictHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    public function update(Request $request, string $module, string $code): JsonResponse
    {
        if ($errorResponse = $this->authorizeAndValidateModule($request, $module, ApiClient::SCOPE_SETTINGS_WRITE)) {
            return $errorResponse;
        }

        try {
            $record = $this->settingsService->update($module, $code, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully.',
                'data' => (new SettingsResource($record))->resolve($request),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Request $request, string $module, string $code): JsonResponse
    {
        if ($errorResponse = $this->authorizeAndValidateModule($request, $module, ApiClient::SCOPE_SETTINGS_WRITE)) {
            return $errorResponse;
        }

        try {
            $result = $this->settingsService->delete($module, $code);

            return response()->json([
                'success' => true,
                'message' => ($result['already_deleted'] ?? false)
                    ? 'Record already deleted.'
                    : 'Record deleted successfully.',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (ConflictHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Enforce API scope authorization and module validation.
     */
    protected function authorizeAndValidateModule(Request $request, string $module, string $requiredScope): ?JsonResponse
    {
        /** @var ApiClient|null $apiClient */
        $apiClient = $request->attributes->get('api_client');

        if (! $apiClient instanceof ApiClient || ! $apiClient->hasScope($requiredScope)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ], 403);
        }

        if (! $this->moduleResolver->isValid($module)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Data Sync module.',
            ], 404);
        }

        return null;
    }

    /**
     * Enforce API scope authorization and module validation before invoking SettingsService.
     */
    protected function executeOperation(Request $request, string $module, string $requiredScope, callable $operation): JsonResponse
    {
        if ($errorResponse = $this->authorizeAndValidateModule($request, $module, $requiredScope)) {
            return $errorResponse;
        }

        try {
            $result = $operation();

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (BadMethodCallException) {
            return response()->json([
                'success' => false,
                'message' => 'This Data Sync operation is not implemented yet.',
            ], 501);
        }
    }
}


<?php

namespace App\Http\Controllers\Api\DataSync;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataSync\ProjectSyncRequest;
use App\Http\Requests\DataSync\ProjectUpdateRequest;
use App\Models\ApiClient;
use App\Services\DataSync\ProjectReferenceResolver;
use App\Services\DataSync\ProjectSyncService;
use App\Services\DataSync\ProjectUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ProjectSyncController extends Controller
{
    public function __construct(
        protected ProjectReferenceResolver $referenceResolver,
        protected ProjectSyncService $projectSyncService,
        protected ?ProjectUpdateService $projectUpdateService = null
    ) {
        $this->projectUpdateService = $projectUpdateService ?? app(ProjectUpdateService::class);
    }

    /**
     * Store synchronized project data from external applications.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var ApiClient|null $apiClient */
        $apiClient = $request->attributes->get('api_client');

        if (! $apiClient instanceof ApiClient || ! $apiClient->hasScope(ApiClient::SCOPE_PROJECTS_WRITE)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ], 403);
        }

        // Idempotency check
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        $cacheKey = null;
        $payloadHash = null;

        if ($idempotencyKey !== '') {
            $cacheKey = sprintf('data-sync:idempotency:projects:%d:%s', $apiClient->id, sha1($idempotencyKey));
            $payloadHash = sha1(json_encode($request->all()));

            if ($cached = Cache::get($cacheKey)) {
                if (($cached['payload_hash'] ?? null) !== $payloadHash) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Idempotency-Key reuse with different request payload.',
                    ], 409);
                }

                $cachedStatus = (int) ($cached['status'] ?? 201);

                return response()->json($cached['body'], $cachedStatus);
            }
        }

        /** @var ProjectSyncRequest $projectSyncRequest */
        $projectSyncRequest = app(ProjectSyncRequest::class);
        $normalizedPayload = $projectSyncRequest->normalizedPayload();

        try {
            $resolvedPayload = $this->referenceResolver->resolve($normalizedPayload);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $syncResult = $this->projectSyncService->setApiClient($apiClient)->sync($resolvedPayload);

            $anyCreated = (bool) ($syncResult['customer']['created'] ?? false);
            if (! $anyCreated) {
                foreach ($syncResult['projects'] ?? [] as $proj) {
                    if (! empty($proj['created'])) {
                        $anyCreated = true;
                        break;
                    }
                }
            }

            $statusCode = $anyCreated ? 201 : 200;

            $responseBody = [
                'success' => true,
                'message' => 'Customer and projects processed successfully.',
                'data' => $syncResult,
            ];

            if ($cacheKey !== null) {
                Cache::put($cacheKey, [
                    'payload_hash' => $payloadHash,
                    'body' => $responseBody,
                    'status' => $statusCode,
                ], now()->addMinutes(10));
            }

            return response()->json($responseBody, $statusCode);
        } catch (ConflictHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (Throwable $e) {
            Log::error('Data Sync Project Creation Failed', [
                'error' => $e->getMessage(),
                'api_client_id' => $apiClient->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process project data synchronization.',
            ], 500);
        }
    }

    /**
     * Update an existing project from external applications.
     */
    public function update(Request $request, string $project_code): JsonResponse
    {
        /** @var ApiClient|null $apiClient */
        $apiClient = $request->attributes->get('api_client');

        if (! $apiClient instanceof ApiClient || ! $apiClient->hasScope(ApiClient::SCOPE_PROJECTS_WRITE)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ], 403);
        }

        /** @var ProjectUpdateRequest $updateRequest */
        $updateRequest = app(ProjectUpdateRequest::class);
        $validatedData = $updateRequest->validated();

        try {
            $result = $this->projectUpdateService->update($project_code, $validatedData, $apiClient);

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully.',
                'data' => $result,
            ], 200);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found.',
            ], 404);
        } catch (ConflictHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Data Sync Project Update Failed', [
                'error' => $e->getMessage(),
                'project_code' => $project_code,
                'api_client_id' => $apiClient->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process project data update.',
            ], 500);
        }
    }
}

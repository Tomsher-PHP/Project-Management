<?php

namespace App\Http\Controllers\Api\DataSync;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataSync\CustomerUpdateRequest;
use App\Models\ApiClient;
use App\Services\DataSync\CustomerUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class CustomerSyncController extends Controller
{
    public function __construct(
        protected CustomerUpdateService $customerUpdateService
    ) {}

    /**
     * Update an existing customer and manage customer contacts from external applications.
     */
    public function update(Request $request, string $customer_code): JsonResponse
    {
        /** @var ApiClient|null $apiClient */
        $apiClient = $request->attributes->get('api_client');

        if (! $apiClient instanceof ApiClient || ! $apiClient->hasScope(ApiClient::SCOPE_PROJECTS_WRITE)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ], 403);
        }

        /** @var CustomerUpdateRequest $updateRequest */
        $updateRequest = app(CustomerUpdateRequest::class);
        $validatedData = $updateRequest->validated();

        try {
            $result = $this->customerUpdateService->update($customer_code, $validatedData, $apiClient);

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'data' => $result,
            ], 200);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
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
            Log::error('Data Sync Customer Update Failed', [
                'error' => $e->getMessage(),
                'customer_code' => $customer_code,
                'api_client_id' => $apiClient->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process customer data update.',
            ], 500);
        }
    }
}


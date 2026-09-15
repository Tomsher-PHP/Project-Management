<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeetingResource;
use App\Models\ApiClient;
use App\Services\MeetingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    protected MeetingService $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    /**
     * Get active scheduled meetings for external server-to-server integration.
     */
    public function active(Request $request): JsonResponse
    {
        /** @var ApiClient|null $apiClient */
        $apiClient = $request->attributes->get('api_client');

        if (! $apiClient || ! $apiClient->hasScope(ApiClient::SCOPE_MEETINGS_READ)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $meetings = $this->meetingService->getActiveMeetings();

        return response()->json([
            'success' => true,
            'data' => MeetingResource::collection($meetings),
        ]);
    }
}

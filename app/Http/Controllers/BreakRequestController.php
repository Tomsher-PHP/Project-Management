<?php

namespace App\Http\Controllers;

use App\Http\Requests\BreakWorkStoreRequest;
use App\Services\BreakRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class BreakRequestController extends Controller
{
    public function store(BreakWorkStoreRequest $request, BreakRequestService $breakRequestService): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $startedAt = $request->normalizedStartedAt();
        $endedAt = $request->normalizedEndedAt();

        abort_unless($startedAt && $endedAt, Response::HTTP_UNPROCESSABLE_ENTITY);

        $breakWorkRequest = $breakRequestService->store(
            $user,
            $request->validated('work_date'),
            $startedAt,
            $endedAt,
            $request->durationSeconds(),
            $request->validated('description')
        );

        $message = 'Break work request submitted successfully.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'id' => $breakWorkRequest->id,
                    'status' => $breakWorkRequest->status,
                    'processing_status' => $breakWorkRequest->processing_status,
                ],
            ], Response::HTTP_CREATED);
        }

        return redirect()->back()->with('success', $message);
    }
}

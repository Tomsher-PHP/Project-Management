<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskTimeExtendStoreRequest;
use App\Models\Task;
use App\Models\TaskExtendTimeRequest;
use App\Services\TaskTimeExtendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskTimeExtendController extends Controller
{
    protected $service;

    public function __construct(TaskTimeExtendService $service)
    {
        $this->service = $service;
    }

    public function store(TaskTimeExtendStoreRequest $request, Task $task): JsonResponse
    {
        // Check if current user is assignee
        if ((int)Auth::id() !== (int)$task->current_assignee_id) {
            return response()->json([
                'status' => false,
                'message' => 'Only the task assignee can request an estimate change.'
            ], 403);
        }

        // Find existing pending request
        $pendingRequest = TaskExtendTimeRequest::where('task_id', $task->id)
            ->where('status', 'pending')
            ->first();

        $validated = $request->validated();

        if ($pendingRequest) {
            // Only the original requester may edit their own pending request
            if ((int)$pendingRequest->user_id !== (int)Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only the original requester can edit this pending request.'
                ], 403);
            }

            $this->service->updateRequest($pendingRequest, $validated);

            return response()->json([
                'status' => true,
                'message' => 'Estimate change request updated successfully.'
            ]);
        }

        $this->service->createRequest($task, $validated);

        return response()->json([
            'status' => true,
            'message' => 'Estimate change request submitted successfully.'
        ]);
    }

    public function pending(Task $task): JsonResponse
    {
        // Check if current user is assignee
        if ((int)Auth::id() !== (int)$task->current_assignee_id) {
            return response()->json([
                'status' => false,
                'message' => 'Only the task assignee can request an estimate change.'
            ], 403);
        }

        // Find the latest pending request
        $pendingRequest = TaskExtendTimeRequest::where('task_id', $task->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'status' => true,
                'data' => [
                    'new_estimated_time_minutes' => (int) ($pendingRequest->new_estimated_time_seconds / 60),
                    'reason' => $pendingRequest->reason,
                ]
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => null
        ]);
    }
}

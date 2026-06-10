<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskExceedTimeStoreRequest;
use App\Models\Task;
use App\Models\TaskExceedTimeRequest;
use App\Services\TaskTimeExceedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskExceedTimeController extends Controller
{
    protected $service;

    public function __construct(TaskTimeExceedService $service)
    {
        $this->service = $service;
    }

    public function store(TaskExceedTimeStoreRequest $request, Task $task): JsonResponse
    {
        // Check if current user is assignee
        if ((int)Auth::id() !== (int)$task->current_assignee_id) {
            return response()->json([
                'status' => false,
                'message' => 'Only the task assignee can request an estimate change.'
            ], 403);
        }

        // Check if pending request exists
        $hasPending = TaskExceedTimeRequest::where('task_id', $task->id)->where('status', 'pending')->exists();

        if ($hasPending) {
            return response()->json([
                'status' => false,
                'message' => 'There is already a pending estimate change request for this task.'
            ], 422);
        }

        $validated = $request->validated();

        $this->service->createRequest($task, $validated);

        return response()->json([
            'status' => true,
            'message' => 'Estimate change request submitted successfully.'
        ]);
    }
}

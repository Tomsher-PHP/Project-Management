<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExceedTimeRequest;
use Illuminate\Support\Facades\Auth;

class TaskTimeExceedService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Create a task exceed time request.
     */
    public function createRequest(Task $task, array $data): TaskExceedTimeRequest
    {
        return TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'estimated_time_seconds' => $task->estimated_time_seconds ?? 0,
            'new_estimated_time_seconds' => array_key_exists('new_estimated_time_minutes', $data)
                ? (int) (($data['new_estimated_time_minutes'] ?? 0) * 60)
                : 0,
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
        ]);
    }

    /**
     * Update an existing task exceed time request.
     */
    public function updateRequest(TaskExceedTimeRequest $request, array $data): TaskExceedTimeRequest
    {
        $request->update([
            'new_estimated_time_seconds' => array_key_exists('new_estimated_time_minutes', $data)
                ? (int) (($data['new_estimated_time_minutes'] ?? 0) * 60)
                : 0,
            'reason' => $data['reason'] ?? null,
        ]);

        return $request;
    }
}


<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExtendTimeRequest;
use Illuminate\Support\Facades\Auth;

class TaskTimeExtendService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * Create a task extend time request.
     */
    public function createRequest(Task $task, array $data): TaskExtendTimeRequest
    {
        $user = Auth::user();

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => $task->estimated_time_seconds ?? 0,
            'new_estimated_time_seconds' => array_key_exists('new_estimated_time_minutes', $data)
                ? (int) (($data['new_estimated_time_minutes'] ?? 0) * 60)
                : 0,
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
        ]);

        $this->notificationService->notifyTaskTimeExtendRequest($user, $task, $request);

        return $request;
    }

    /**
     * Update an existing task extend time request.
     */
    public function updateRequest(TaskExtendTimeRequest $request, array $data): TaskExtendTimeRequest
    {
        $request->update([
            'new_estimated_time_seconds' => array_key_exists('new_estimated_time_minutes', $data)
                ? (int) (($data['new_estimated_time_minutes'] ?? 0) * 60)
                : 0,
            'reason' => $data['reason'] ?? null,
        ]);

        $user = Auth::user();
        if ($user) {
            $request->loadMissing('task');
            if ($request->task) {
                $this->notificationService->notifyTaskTimeExtendRequest($user, $request->task, $request);
            }
        }

        return $request;
    }
}

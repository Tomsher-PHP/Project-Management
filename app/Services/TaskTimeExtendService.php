<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
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

    public function visibleRequestQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        if ($user->is_super_admin) {
            return TaskExtendTimeRequest::query();
        }

        $accessibleUserIds = User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->push($user->id)
            ->unique()
            ->values()
            ->all();

        return TaskExtendTimeRequest::query()->whereIn('user_id', $accessibleUserIds);
    }

    public function getRequests(User $user, int $perPage, string $status, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->visibleRequestQuery($user)
            ->where('status', $status)
            ->with([
                'user:id,name',
                'user.primaryAttachment',
                'task:id,name,project_id,estimated_time_seconds',
                'task.project:id,name',
                'rejector:id,name',
            ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('task', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['project_id'])) {
            $projectId = $filters['project_id'];
            $query->whereHas('task', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        if (!empty($filters['user_id'])) {
            $query->whereIn('user_id', (array) $filters['user_id']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function getFilterOptions(User $user): array
    {
        $visibleQuery = $this->visibleRequestQuery($user);

        $userIds = (clone $visibleQuery)->distinct()->pluck('user_id')->filter();

        $taskIds = (clone $visibleQuery)->distinct()->pluck('task_id')->filter();
        $projectIds = $taskIds->isEmpty()
            ? collect()
            : Task::query()->whereIn('id', $taskIds)->distinct()->pluck('project_id')->filter();

        return [
            'users' => $userIds->isEmpty()
                ? collect()
                : User::query()->whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']),
            'projects' => $projectIds->isEmpty()
                ? collect()
                : Project::query()->whereIn('id', $projectIds)->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function reject(User $user, TaskExtendTimeRequest $request, string $reason): void
    {
        if (!$request->isPending()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'extend_request' => 'Only pending extend requests can be reviewed.',
            ]);
        }

        $request->update([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => trim($reason),
        ]);

        $request->loadMissing(['task', 'user']);
        if ($request->task && $request->user) {
            $this->notificationService->notifyTaskTimeExtendRequestRejected($request, $request->task, $request->user);
        }
    }

    public function approve(User $user, TaskExtendTimeRequest $request, int $newEstimatedMinutes): void
    {
        if (!$request->isPending()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'extend_request' => 'Only pending extend requests can be reviewed.',
            ]);
        }

        $request->loadMissing('task');
        $task = $request->task;
        if (!$task) {
            throw new \Exception('Associated task not found.');
        }

        $newEstimatedSeconds = $newEstimatedMinutes * 60;

        // Preserve initial_estimated_time_seconds
        if ((int)$task->initial_estimated_time_seconds === 0) {
            $task->initial_estimated_time_seconds = $task->estimated_time_seconds;
        }

        $task->estimated_time_seconds = $newEstimatedSeconds;
        $task->save();

        $request->update([
            'status' => 'approved',
            'new_estimated_time_seconds' => $newEstimatedSeconds,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
        ]);
    }
}

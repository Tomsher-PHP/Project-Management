<?php

namespace App\Services;

use App\Models\HandoffPurpose;
use App\Models\HandoffRequest;
use App\Models\HandoffRequestAction;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class HandoffServices
{
    public function getHandoffRequestsForList(User $user, int $perPage, array $filters = [])
    {
        $query = HandoffRequest::sort($filters)->with([
            'project',
            'projectMilestone',
            'projectSprint',
            'sourceTask',
            'user',
            'createdTask'
        ]);

        if (!$user->is_super_admin) {
            if ($user->can('handoff_request.view_all')) {
                $query->whereHas('project', function ($q) use ($user) {
                    $q->accessibleBy($user);
                });
            } else {
                $accessibleUserIds = User::query()
                    ->accessibleBy($user)
                    ->pluck('users.id')
                    ->toArray();

                $accessibleUserIds[] = $user->id;
                $query->whereIn('user_id', $accessibleUserIds);
            }
        }

        return $query->filter($filters)->latest()->paginate($perPage);
    }

    public function getFilterOptions(User $user): array
    {
        $query = HandoffRequest::query();

        if (!$user->is_super_admin) {
            if ($user->can('handoff_request.view_all')) {
                $query->whereHas('project', function ($q) use ($user) {
                    $q->accessibleBy($user);
                });
            } else {
                $accessibleUserIds = User::query()
                    ->accessibleBy($user)
                    ->pluck('users.id')
                    ->toArray();

                $accessibleUserIds[] = $user->id;
                $query->whereIn('user_id', $accessibleUserIds);
            }
        }

        $projectIds = (clone $query)->distinct()->pluck('project_id')->filter();
        $userIds = (clone $query)->distinct()->pluck('user_id')->filter();
        $milestoneIds = (clone $query)->distinct()->pluck('project_milestone_id')->filter();
        $sprintIds = (clone $query)->distinct()->pluck('project_sprint_id')->filter();
        $purposes = clone $query->distinct()->pluck('purpose')->filter();

        $purposeOptions = [];
        foreach ($purposes as $p) {
            $purposeOptions[$p] = $p;
        }

        return [
            'projects' => $projectIds->isEmpty() ? collect() : Project::query()->whereIn('id', $projectIds)->orderBy('name')->get(['id', 'name']),
            'users' => $userIds->isEmpty() ? collect() : User::query()->whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']),
            'milestones' => $milestoneIds->isEmpty() ? collect() : ProjectMilestone::query()->whereIn('id', $milestoneIds)->orderBy('name')->get(['id', 'name']),
            'sprints' => $sprintIds->isEmpty() ? collect() : ProjectSprint::query()->whereIn('id', $sprintIds)->orderBy('name')->get(['id', 'name']),
            'purposes' => $purposeOptions,
        ];
    }

    public function createHandoffRequest(array $data, int $userId): HandoffRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            $handoffRequest = HandoffRequest::create([
                'project_id' => $data['project_id'],
                'project_milestone_id' => $data['project_milestone_id'] ?? null,
                'project_sprint_id' => $data['project_sprint_id'] ?? null,
                'source_task_id' => $data['source_task_id'] ?? null,
                'user_id' => $userId,
                'purpose' => $data['purpose'],
                'description' => $data['description'],
                'status' => 0, // pending
            ]);

            HandoffRequestAction::create([
                'handoff_request_id' => $handoffRequest->id,
                'user_id' => $userId,
                'action' => HandoffRequestAction::REQUEST_CREATED,
            ]);

            HandoffPurpose::firstOrCreate([
                'name' => $data['purpose'],
            ]);

            app(\App\Services\NotificationService::class)->notifyHandoffRequestCreated($handoffRequest, User::find($userId));

            return $handoffRequest;
        });
    }

    public function markAsNoted(HandoffRequest $handoffRequest, int $userId): HandoffRequest
    {
        return DB::transaction(function () use ($handoffRequest, $userId) {
            $handoffRequest->update([
                'status' => HandoffRequest::STATUS_NOTED,
            ]);

            HandoffRequestAction::create([
                'handoff_request_id' => $handoffRequest->id,
                'user_id' => $userId,
                'action' => HandoffRequestAction::REQUEST_NOTED,
            ]);

            app(\App\Services\NotificationService::class)->notifyHandoffRequestNoted($handoffRequest, User::find($userId));

            return $handoffRequest;
        });
    }

    public function markAsAssigned(int $handoffRequestId, Task $createdTask, User $user, ?string $comment = null): void
    {
        $handoffRequest = HandoffRequest::lockForUpdate()->find($handoffRequestId);
        if (!$handoffRequest) {
            throw new \Exception("Handoff request not found.");
        }

        if (!in_array($handoffRequest->status, [HandoffRequest::STATUS_PENDING, HandoffRequest::STATUS_NOTED])) {
            throw new \Exception("Only pending or noted handoff requests can be assigned.");
        }

        if ($handoffRequest->project_id !== $createdTask->project_id) {
            throw new \Exception("Created task project must match handoff request project.");
        }

        if ($handoffRequest->project_milestone_id && $handoffRequest->project_milestone_id !== $createdTask->project_milestone_id) {
            throw new \Exception("Created task milestone must match handoff request milestone.");
        }

        if ($handoffRequest->project_sprint_id && $handoffRequest->project_sprint_id !== $createdTask->project_sprint_id) {
            throw new \Exception("Created task sprint must match handoff request sprint.");
        }

        $handoffRequest->update([
            'status' => HandoffRequest::STATUS_ASSIGNED,
            'created_task_id' => $createdTask->id,
        ]);

        HandoffRequestAction::create([
            'handoff_request_id' => $handoffRequest->id,
            'user_id' => $user->id,
            'action' => HandoffRequestAction::REQUEST_ASSIGNED,
            'comment' => $comment,
        ]);

        app(\App\Services\NotificationService::class)->notifyHandoffRequestAssigned($handoffRequest, $createdTask, $user);
    }
}

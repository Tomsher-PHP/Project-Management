<?php

namespace App\Services;

use App\Models\HandoffPurpose;
use App\Models\HandoffRequest;
use App\Models\HandoffRequestAction;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class HandoffServices
{
    public function visibleRequestQuery(User $user): Builder
    {
        $query = HandoffRequest::query();

        if ($user->is_super_admin) {
            return $query;
        }

        $canViewAll = $user->can('handoff_request.view_all');
        $canViewAllUsers = $user->can('user.view_all_users');
        $canViewProject = $user->can('handoff_request.view');

        // Case 1: handoff_request.view_all + user.view_all_users
        if ($canViewAll && $canViewAllUsers) {
            return $query;
        }

        $canViewAccountable = $canViewAll && !$canViewAllUsers;

        if (!$canViewAccountable && !$canViewProject) {
            return $query->where('target_user_id', $user->id);
        }

        return $query->where(function (Builder $q) use ($user, $canViewAccountable, $canViewProject) {
            // Direct recipient access, in addition to the existing role-based access.
            $q->where('target_user_id', $user->id);

            if ($canViewAccountable) {
                // Case 2: Accountable user hierarchy
                $accessibleUserIds = app(UserService::class)->getRequestAccessibleUsers($user);
                $q->orWhereIn('user_id', $accessibleUserIds);
            }

            if ($canViewProject) {
                // Case 3: Project authority (Team Leader or Milestone Owner)
                $authorityClause = function (Builder $authorityQuery) use ($user) {
                    $authorityQuery
                        ->whereHas('project.teamLeader', function (Builder $tlQuery) use ($user) {
                            $tlQuery->whereKey($user->id);
                        })
                        ->orWhereHas('projectMilestone', function (Builder $mQuery) use ($user) {
                            $mQuery->where('owner_id', $user->id);
                        });
                };

                $q->orWhere($authorityClause);
            }
        });
    }

    public function getHandoffAccessibleUsers(User $user): Collection
    {
        $accessibleUserIds = app(UserService::class)->getRequestAccessibleUsers($user);

        return User::query()
            ->whereIn('id', $accessibleUserIds)
            ->orderBy('name')
            ->get();
    }

    public function getHandoffRequestNotificationRecipients(HandoffRequest $handoffRequest, User $requester): Collection
    {
        $handoffRequest->loadMissing(['project.teamLeader', 'projectMilestone.owner']);

        $recipientIds = collect();

        // The intended recipient is added to the established recipient set below.
        if ($handoffRequest->target_user_id) {
            $recipientIds->push((int) $handoffRequest->target_user_id);
        }

        // 1. Project Team Leader
        if ($handoffRequest->project?->teamLeader?->id) {
            $recipientIds->push((int) $handoffRequest->project->teamLeader->id);
        }

        // 2. Project Milestone Owner
        if ($handoffRequest->projectMilestone?->owner_id) {
            $recipientIds->push((int) $handoffRequest->projectMilestone->owner_id);
        }

        // 3. Global viewers: Super admins + users with handoff_request.view_all AND user.view_all_users
        $globalViewerIds = User::query()
            ->where(function (Builder $query) {
                $query->where('is_super_admin', true)
                    ->orWhere(function (Builder $permQuery) {
                        $permQuery->permission(['handoff_request.view_all'])
                            ->permission(['user.view_all_users']);
                    });
            })
            ->pluck('id');

        $recipientIds = $recipientIds->merge($globalViewerIds);

        // 4. Accountable viewers: Users with handoff_request.view_all who have $requester in their accessible users hierarchy
        $reporterChainIds = User::getReporterChainUserIds($requester->id);
        if (!empty($reporterChainIds)) {
            $accountableViewerIds = User::query()
                ->whereIn('id', $reporterChainIds)
                ->permission('handoff_request.view_all')
                ->pluck('id');

            $recipientIds = $recipientIds->merge($accountableViewerIds);
        }

        // Filter and deduplicate recipient IDs, excluding the requester
        $finalRecipientIds = $recipientIds
            ->filter()
            ->map(fn($id) => (int) $id)
            ->reject(fn($id) => $id === (int) $requester->id)
            ->unique()
            ->values()
            ->all();

        if (empty($finalRecipientIds)) {
            return collect();
        }

        return User::query()->whereIn('id', $finalRecipientIds)->get();
    }

    public function getHandoffRequestsForList(User $user, int $perPage, array $filters = [])
    {
        $query = $this->visibleRequestQuery($user)->sort($filters)->with([
            'project',
            'projectMilestone',
            'projectSprint',
            'sourceTask',
            'user',
            'targetUser',
            'createdTask'
        ]);

        return $query->filter($filters)->latest()->paginate($perPage);
    }

    public function getFilterOptions(User $user): array
    {
        $query = $this->visibleRequestQuery($user);

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
                'target_user_id' => $data['target_user_id'] ?? null,
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

            $requester = User::find($userId);
            $recipients = $this->getHandoffRequestNotificationRecipients($handoffRequest, $requester);

            app(NotificationService::class)->notifyHandoffRequestCreated($handoffRequest, $recipients, $requester);

            return $handoffRequest->load('targetUser');
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

            app(NotificationService::class)->notifyHandoffRequestNoted($handoffRequest, User::find($userId));

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

        if ($createdTask->request_type === 'self') {
            if (!Gate::allows('request-task')) {
                throw new \Exception("You do not have permission to request a task.");
            }
            if ((int) $handoffRequest->target_user_id !== (int) $user->id) {
                throw new \Exception("Only the designated target user can request a task from this handoff.");
            }
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

        app(NotificationService::class)->notifyHandoffRequestAssigned($handoffRequest, $createdTask, $user);
    }
}

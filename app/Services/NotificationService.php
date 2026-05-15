<?php

namespace App\Services;

use App\Models\HandoffRequest;
use App\Models\Task;
use App\Models\TaskTimeLogChangeRequest;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class NotificationService
{
    // Single user
    public function send(int $userId, string $title, string $message, ?string $url = null): void
    {
        $user = User::find($userId);

        if (!$user) {
            return;
        }

        $user->notify(new TaskAssignedNotification(
            $title,
            $message,
            $url
        ));
    }

    // Multiple users
    public function sendToMany(array $userIds, string $title, string $message, ?string $url = null): void
    {
        User::whereIn('id', $userIds)
            ->chunk(50, function ($users) use ($title, $message, $url) {
                Notification::send(
                    $users,
                    new TaskAssignedNotification($title, $message, $url)
                );
            });
    }

    // Task assignment notification to assignee when task is created or updated
    public function sendTaskAssignmentIfNeeded(Task $task, ?int $currentAssigneeId, ?int $previousAssigneeId = null): void
    {
        $currentAssigneeId = filled($currentAssigneeId) ? (int) $currentAssigneeId : null;
        $previousAssigneeId = filled($previousAssigneeId) ? (int) $previousAssigneeId : null;

        if (!$currentAssigneeId || $currentAssigneeId === $previousAssigneeId) {
            return;
        }

        $task->loadMissing('project:id,name');

        $authUser = auth()->user();
        $isSelfAssigned = $authUser && (int) $authUser->id === $currentAssigneeId;

        $projectName = $task->project?->name ?: 'Untitled Project';
        $title = $previousAssigneeId ? 'Task Reassigned' : 'Task Assigned';

        if ($isSelfAssigned) {
            $message = $previousAssigneeId
                ? "You reassigned task '{$task->name}' to yourself in project '{$projectName}'."
                : "You assigned yourself to task '{$task->name}' in project '{$projectName}'.";
        } else {
            $actorName = $authUser?->name ?? 'A team member';

            $message = $previousAssigneeId
                ? "{$actorName} reassigned task '{$task->name}' to you in project '{$projectName}'."
                : "{$actorName} assigned you to task '{$task->name}' in project '{$projectName}'.";
        }

        $this->send(
            $currentAssigneeId,
            $title,
            $message,
            route('tasks.edit', $task)
        );
    }

    // Task status change notification to assignee, reporter, manager and super admins
    public function notifyTaskStatusChanged(Task $task, User $actor, string $oldStatus, string $newStatus): void
    {
        $userIds = $task->getRelatedUsers()
            ->pluck('id')
            ->filter()
            ->reject(fn($userId) => (int) $userId === (int) $task->current_assignee_id)
            ->unique()
            ->values()
            ->all();

        $taskName = Str::limit($task->name ?? 'Task', 50, '...');
        $projectName = $task->project?->name ?? 'Project';

        $title = "Task Status Updated";
        $url = url('tasks/' . $task->id . '/edit');

        User::whereIn('id', $userIds)->chunk(50, function ($users) use ($actor, $taskName, $projectName, $oldStatus, $newStatus, $title, $url) {
            foreach ($users as $user) {
                $actorLabel = $user->id === $actor->id ? 'You' : $actor->name;
                $message = "{$actorLabel} moved '{$taskName}' in '{$projectName}' from {$oldStatus} to {$newStatus}";
                $this->send($user->id, $title, $message, $url);
            }
        });
    }

    // Notify related users when a task request is created
    public function notifyTaskRequestCreated(Task $task): void
    {
        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $userIds = $task->getRelatedUsers()
            ->pluck('id')
            ->filter()
            ->reject(fn($userId) => (int) $userId === (int) $task->current_assignee_id)
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return;
        }

        $taskName = Str::limit($task->name ?? 'Task', 50, '...');
        $requesterName = $task->currentAssignee?->name ?? 'A team member';
        $projectName = $task->project?->name ?? 'Project';

        $this->sendToMany(
            $userIds,
            'Task Request Created',
            "{$requesterName} requested task '{$taskName}' in '{$projectName}'.",
            route('tasks.edit', $task)
        );
    }

    // Notify only the requested user when their task request is approved or rejected
    public function notifyTaskRequestReviewed(Task $task, User $reviewer, string $action, ?string $description = null): void
    {
        if (! $task->current_assignee_id) {
            return;
        }

        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $isRejected = $action === 'reject';
        $taskName = Str::limit($task->name ?? 'Task', 50, '...');
        $projectName = $task->project?->name ?? 'Project';
        $reviewerName = $reviewer->name ?? 'A team member';
        $reviewLabel = $isRejected ? 'rejected' : 'approved';

        $message = "{$reviewerName} {$reviewLabel} your task request '{$taskName}' in '{$projectName}'.";

        if ($isRejected && filled($description)) {
            $message .= ' Description: ' . trim((string) $description);
        }

        $this->send(
            (int) $task->current_assignee_id,
            $isRejected ? 'Task Request Rejected' : 'Task Request Approved',
            $message,
            route('tasks.edit', $task)
        );
    }

    public function notifyTaskTimeLogChangeRequestCreated(TaskTimeLogChangeRequest $changeRequest): void
    {
        $changeRequest->loadMissing([
            'timeLog.task.project:id,name',
            'user:id,name',
            'user.reporter' => fn($query) => $query->select('users.id', 'users.name'),
            'user.manager' => fn($query) => $query->select('users.id', 'users.name'),
        ]);

        $task = $changeRequest->timeLog?->task;

        if (! $task) {
            return;
        }

        $recipientIds = collect([
            $changeRequest->user?->reporter?->id,
            $changeRequest->user?->manager?->id,
        ])
            ->filter()
            ->reject(fn($userId) => (int) $userId === (int) $changeRequest->user_id)
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return;
        }

        $taskName = Str::limit($task->name ?? 'Task', 50, '...');
        $projectName = $task->project?->name ?? 'Project';
        $requesterName = $changeRequest->user?->name ?? 'A team member';

        $this->sendToMany(
            $recipientIds,
            'Task Time Log Change Request Created',
            "{$requesterName} requested a time log change for task '{$taskName}' in '{$projectName}'.",
            route('tasks.edit', $task)
        );
    }

    public function notifyTaskTimeLogChangeRequestReviewed(
        TaskTimeLogChangeRequest $changeRequest,
        User $reviewer,
        string $action,
        ?string $description = null
    ): void {
        if (! $changeRequest->user_id) {
            return;
        }

        $changeRequest->loadMissing([
            'timeLog.task.project:id,name',
            'user:id,name',
        ]);

        $task = $changeRequest->timeLog?->task;

        if (! $task) {
            return;
        }

        $isRejected = $action === 'reject';
        $taskName = Str::limit($task->name ?? 'Task', 50, '...');
        $projectName = $task->project?->name ?? 'Project';
        $reviewerName = $reviewer->name ?? 'A team member';
        $reviewLabel = $isRejected ? 'rejected' : 'approved';

        $message = "{$reviewerName} {$reviewLabel} your time log change request for task '{$taskName}' in '{$projectName}'.";

        if ($isRejected && filled($description)) {
            $message .= ' Description: ' . trim((string) $description);
        }

        $this->send(
            (int) $changeRequest->user_id,
            $isRejected ? 'Task Time Log Change Request Rejected' : 'Task Time Log Change Request Approved',
            $message,
            route('tasks.edit', $task)
        );
    }

    public function notifyTaskTimerStoppedByOtherUser(Task $task, User $actor): void
    {
        $assigneeId = (int) ($task->current_assignee_id ?? 0);

        if (! $assigneeId || $assigneeId === (int) $actor->id) {
            return;
        }

        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $taskName = Str::limit($task->name ?? 'Task', 50, '...');
        $projectName = $task->project?->name ?? 'Project';
        $actorName = $actor->name ?? 'A team member';

        $this->send(
            $assigneeId,
            'Task Timer Stopped',
            "{$actorName} stopped your running timer for task '{$taskName}' in project '{$projectName}'.",
            route('tasks.edit', $task)
        );
    }

    public function notifyTaskTimerStoppedBecauseStatusChanged(Task $task, User $actor, string $statusName): void
    {
        $assigneeId = (int) ($task->current_assignee_id ?? 0);

        if (! $assigneeId || $assigneeId === (int) $actor->id) {
            return;
        }

        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $taskName = Str::limit($task->name ?? 'Task', 50, '...');
        $projectName = $task->project?->name ?? 'Project';
        $actorName = $actor->name ?? 'A team member';

        $this->send(
            $assigneeId,
            'Task Timer Stopped',
            "{$actorName} changed task '{$taskName}' in project '{$projectName}' to '{$statusName}', so your running timer was stopped.",
            route('tasks.edit', $task)
        );
    }

    public function notifyTaskStart(Task $task): bool
    {
        $assigneeId = (int) ($task->current_assignee_id ?? 0);

        if (! $assigneeId) {
            return false;
        }

        if (($task->status?->type ?? null) !== 'pending') {
            return false;
        }

        if (! $task->due_date_time || (int) ($task->estimated_time_seconds ?? 0) <= 0) {
            return false;
        }

        if ($task->timeLogs()->exists()) {
            return false;
        }

        //update start_notify_at avoid duplicate
        $task->updateQuietly(['start_notify_at' => now()]);

        $startAt = $task->due_date_time->copy()->subSeconds((int) $task->estimated_time_seconds);
        $title = 'Task Start Reminder';
        $taskName = Str::limit($task->name ?? 'Task', 50, '...');
        $projectName = $task->project?->name ?? 'Project';
        $startAtLabel = $startAt->timezone(config('constants.timezone', config('app.timezone')))->format('d-M-Y h:i A');
        $dueAtLabel = $task->due_date_time->copy()->timezone(config('constants.timezone', config('app.timezone')))->format('d-M-Y h:i A');
        $message = "Please start task '{$taskName}' in project '{$projectName}' by {$startAtLabel} to stay on track for the due time {$dueAtLabel}.";
        $url = route('tasks.edit', $task);

        $this->send($assigneeId, $title, $message, $url);

        return true;
    }

    public function notifyHandoffRequestAssigned(HandoffRequest $handoffRequest, Task $createdTask, User $actor): void
    {
        $requesterId = (int) $handoffRequest->user_id;

        if (!$requesterId || $requesterId === (int) $actor->id) {
            return;
        }

        $handoffRequest->loadMissing('project:id,name');

        $taskName = Str::limit($createdTask->name ?? 'Task', 50, '...');
        $projectName = $handoffRequest->project?->name ?? 'Project';
        $actorName = $actor->name ?? 'A team member';

        $title = 'Handoff Request Assigned';
        $message = "{$actorName} assigned your handoff request (#{$handoffRequest->id}) and a new task '{$taskName}' (#{$createdTask->id}) was created in project '{$projectName}'.";

        $this->send(
            $requesterId,
            $title,
            $message,
            route('tasks.edit', $createdTask)
        );
    }

    public function notifyHandoffRequestCreated(HandoffRequest $handoffRequest, User $requester): void
    {
        $handoffRequest->loadMissing('project.teamLeader');

        $authorizedUserIds = User::permission(['handoff_request.view', 'handoff_request.view_all'])
            ->pluck('id')
            ->toArray();

        $teamLeaderId = $handoffRequest->project?->teamLeader?->id;

        $recipientIds = collect($authorizedUserIds)
            ->push($teamLeaderId)
            ->filter()
            ->reject(fn($id) => (int) $id === (int) $requester->id)
            ->unique()
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        $projectName = $handoffRequest->project?->name ?? 'Project';
        $title = 'New Handoff Request';
        $message = "{$requester->name} created a new handoff request (#{$handoffRequest->id}) for project '{$projectName}'.";
        $url = route('handoff_requests.index', ['request_status' => 'pending']);

        $this->sendToMany($recipientIds, $title, $message, $url);
    }

    public function notifyHandoffRequestNoted(HandoffRequest $handoffRequest, User $actor): void
    {
        $requesterId = (int) $handoffRequest->user_id;

        if (!$requesterId || $requesterId === (int) $actor->id) {
            return;
        }

        $handoffRequest->loadMissing('project:id,name');

        $projectName = $handoffRequest->project?->name ?? 'Project';
        $title = 'Handoff Request Noted';
        $message = "{$actor->name} marked your handoff request (#{$handoffRequest->id}) in project '{$projectName}' as noted.";
        $url = route('handoff_requests.index', ['request_status' => 'noted']);

        $this->send($requesterId, $title, $message, $url);
    }
}

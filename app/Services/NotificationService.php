<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
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
}

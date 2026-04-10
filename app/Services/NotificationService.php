<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Notification;

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
}

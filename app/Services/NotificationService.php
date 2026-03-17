<?php

namespace App\Services;

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
}

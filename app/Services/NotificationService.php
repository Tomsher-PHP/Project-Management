<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TaskAssignedNotification;

class NotificationService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

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
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $user->notify(new TaskAssignedNotification($title, $message, $url));
        }
    }
}

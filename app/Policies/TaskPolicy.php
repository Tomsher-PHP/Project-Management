<?php

namespace App\Policies;

use App\Models\ProjectTask;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin || $user->can('task.view_all_tasks');
    }

    public function view(User $user, ProjectTask $task): bool
    {
        if ($user->is_super_admin || $user->can('task.view_all_tasks')) {
            return true;
        }

        if ((int) $task->added_by === (int) $user->id) {
            return true;
        }

        return (int) ($task->current_assignee_id ?? 0) === (int) $user->id;
    }

    public function update(User $user, ProjectTask $task): bool
    {
        return $user->can('task.edit') && $this->view($user, $task);
    }
}

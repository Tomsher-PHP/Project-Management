<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin || $user->can('task.view_all_tasks');
    }

    public function view(User $user, Task $task): bool
    {
        // Skip break work requested tasks
        if ($task->break_work_request_id) {
            return false;
        }

        if ($user->is_super_admin || $user->can('task.view_all_tasks')) {
            return true;
        }

        if ((int) ($task->current_assignee_id ?? 0) === (int) $user->id) {
            return true;
        }

        $task->load([
            'project.teamLeader',
            'projectMilestone:id,owner_id,name',
        ]);

        if ((int) ($task->project?->teamLeader?->id ?? 0) === (int) $user->id) {
            return true;
        }

        if ((int) ($task->projectMilestone?->owner_id ?? 0) === (int) $user->id) {
            return true;
        }

        return $task->assignmentLogs()
            ->where('user_id', $user->id)
            ->where('worked_time_seconds', '>', 0)
            ->exists();
    }

    public function update(User $user, Task $task): bool
    {
        return $user->can('task.edit') && $this->view($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('task.delete') && $this->view($user, $task);
    }

    public function move(User $user, Task $task): bool
    {
        return $user->can('task.move') && $this->view($user, $task);
    }
}

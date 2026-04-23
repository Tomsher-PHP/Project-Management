<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;

class TaskQueryService
{
    public function baseQuery(User $user)
    {
        return Task::query()->accessibleBy($user);
    }
}

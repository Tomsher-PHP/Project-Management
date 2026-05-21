<?php

namespace App\Services;

use App\Models\BreakWorkRequest;
use App\Models\User;
use Carbon\Carbon;

class BreakRequestService
{
    public function store(User $user, string $workDate, Carbon $startedAt, Carbon $endedAt, int $durationSeconds, string $description): BreakWorkRequest
    {
        return BreakWorkRequest::create([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'description' => $description,
            'status' => BreakWorkRequest::STATUS_PENDING,
            'processing_status' => BreakWorkRequest::PROCESSING_STATUS_PENDING,
            'added_by' => $user->id,
        ]);
    }
}

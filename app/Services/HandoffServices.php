<?php

namespace App\Services;

use App\Models\HandoffPurpose;
use App\Models\HandoffRequest;
use App\Models\HandoffRequestAction;
use Illuminate\Support\Facades\DB;

class HandoffServices
{

    public function createHandoffRequest(array $data, int $userId): HandoffRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            $handoffRequest = HandoffRequest::create([
                'project_id' => $data['project_id'],
                'project_milestone_id' => $data['project_milestone_id'] ?? null,
                'project_sprint_id' => $data['project_sprint_id'] ?? null,
                'source_task_id' => $data['source_task_id'] ?? null,
                'user_id' => $userId,
                'purpose' => $data['purpose'],
                'description' => $data['description'],
                'status' => 0, // pending
            ]);

            HandoffRequestAction::create([
                'handoff_request_id' => $handoffRequest->id,
                'user_id' => $userId,
                'action' => 0, // created
            ]);

            // create new handoff purpose if not exists
            HandoffPurpose::firstOrCreate([
                'name' => $data['purpose'],
            ]);

            return $handoffRequest;
        });
    }
}

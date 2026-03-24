<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectStatusHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectServices
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            // Handle default start date
            $startDate = $data['start_date'] ?? now()->toDateString();

            // Handle end date logic
            if (empty($data['end_date'])) {
                $data['end_date'] = Carbon::parse($startDate)->addDays(7)->toDateString();
            }

            // Map correct column (important fix)
            $data['status_id'] = $data['project_status'];
            unset($data['project_status']);

            // Create project
            $project = Project::create([
                'project_code' => Project::generateProjectCode(),
                'name' => $data['name'],
                'customer_id' => $data['customer_id'] ?? null,
                'project_type' => $data['project_type'],
                'priority' => $data['priority'],
                'status_id' => $data['status_id'],
                'start_date' => $startDate,
                'internal_end_date' => $data['end_date'],
            ]);

            // Insert status history
            ProjectStatusHistory::create([
                'project_id' => $project->id,
                'status_id' => $project->status_id,
            ]);

            return $project;
        });
    }
}

<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectStatusHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectServices
{
    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
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

    public function update(Project $project, array $data)
    {
        return DB::transaction(function () use ($project, $data) {

            // Map status
            if (isset($data['project_status'])) {
                $data['status_id'] = $data['project_status'];
                unset($data['project_status']);
            }

            // Convert hours → seconds
            if (isset($data['estimated_time_hrs'])) {
                $data['estimated_time_seconds'] = $data['estimated_time_hrs'] * 3600;
                unset($data['estimated_time_hrs']);
            }

            // Default values
            $data['customer_id'] = $data['customer_id'] ?? null;
            $data['default_billable'] = $data['default_billable'] ?? 0;

            // Track old status for history
            $oldStatus = $project->status_id;

            // Update project
            $project->update([
                'name' => $data['name'],
                'customer_id' => $data['customer_id'],
                'priority' => $data['priority'],
                'status_id' => $data['status_id'],
                'start_date' => $data['start_date'] ?? null,
                'internal_end_date' => $data['internal_end_date'] ?? null,
                'client_end_date' => $data['client_end_date'] ?? null,
                'estimated_time_seconds' => $data['estimated_time_seconds'] ?? null,
                'domain' => $data['domain'] ?? null,
                'sales_person_id' => $data['sales_person_id'] ?? null,
                'project_stage' => $data['project_stage'] ?? null,
                'project_category_id' => $data['project_category_id'] ?? null,
                'default_billable' => $data['default_billable'],
            ]);

            // Insert status history ONLY if changed
            if ($oldStatus != $project->status_id) {
                ProjectStatusHistory::create([
                    'project_id' => $project->id,
                    'status_id' => $project->status_id,
                ]);
            }

            // Attach project technologies
            if (isset($data['project_technology_ids'])) {
                $project->technologies()->sync($data['project_technology_ids']);
            }

            return $project->fresh(); // return updated model
        });
    }

    public function uploadFile(Project $project, array $data)
    {
        return DB::transaction(function () use ($project, $data) {
            // 4. Image upload can be handled here if needed
            if (!empty($data['project_file'])) {
                $directory = 'project_files/' . $project->project_code;
                $attachment = $this->attachmentService->upload($data['project_file'], $directory, $project, 'public', 'public', true);
            }
            return $attachment;
        });
    }
}

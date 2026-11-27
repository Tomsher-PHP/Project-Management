<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SystemProjectSeeder extends Seeder
{
    /**
     * Seed the default system project (Misc) and its initial timeline idempotently.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $existingProject = Project::withTrashed()
                ->where('name', 'Misc')
                ->where('is_system', true)
                ->lockForUpdate()
                ->first();

            if ($existingProject) {
                if ($existingProject->trashed()) {
                    $existingProject->restore();
                }

                $this->ensureSystemProjectTimeline($existingProject);

                return;
            }

            $statusId = $this->resolveDefaultProjectStatusId();

            if (! $statusId) {
                throw new RuntimeException(
                    'Unable to seed Misc system project because no active default project status exists.'
                );
            }

            $project = new Project();
            $project->project_code = Project::generateProjectCode();
            $project->name = 'Misc';
            $project->project_flow = 'linear';
            $project->priority = 'medium';
            $project->status_id = $statusId;
            $project->estimated_time_seconds = 0;
            $project->default_task_estimate_seconds = 0;
            $project->default_billable = false;
            $project->is_active = true;
            $project->is_system = true;
            $project->added_by = null;
            $project->customer_id = null;
            $project->project_stage_id = null;
            $project->sales_person_id = null;
            $project->start_date = null;
            $project->end_date = null;
            $project->save();

            $this->ensureSystemProjectTimeline($project);
        });
    }

    private function ensureSystemProjectTimeline(Project $project): void
    {
        $existingTimeline = ProjectTimeline::withTrashed()
            ->where('project_id', $project->id)
            ->orderBy('id')
            ->first();

        if ($existingTimeline) {
            if ($existingTimeline->trashed()) {
                $existingTimeline->restore();
            }

            return;
        }

        ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Original Timeline',
            'type' => 'original',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'customer_end_date' => $project->customer_end_date,
            'estimated_time_seconds' => $project->estimated_time_seconds,
            'customer_estimate_seconds' => $project->customer_estimate_seconds,
            'actual_time_seconds' => 0,
            'sort_order' => 1,
            'created_by' => null,
        ]);
    }

    private function resolveDefaultProjectStatusId(): ?int
    {
        return ProjectStatus::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderByRaw('CASE WHEN sort_order = 1 THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
    }
}

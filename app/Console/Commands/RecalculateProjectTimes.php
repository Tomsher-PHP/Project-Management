<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\ProjectTimeService;
use Illuminate\Console\Command;

class RecalculateProjectTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:recalculate-times {project_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup recalculation for project time metrics.';

    /**
     * Execute the console command.
     */
    public function handle(ProjectTimeService $projectTimeService): int
    {
        $projectId = $this->argument('project_id');

        if ($projectId !== null) {
            $project = Project::query()->find((int) $projectId);

            if (! $project) {
                $this->warn("Project not found for ID: {$projectId}");

                return self::SUCCESS;
            }

            $projectTimeService->recalculateProject($project->id);
            $this->info("Recalculated project times for project #{$project->id} ({$project->name}).");

            return self::SUCCESS;
        }

        $projects = Project::query()
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($projects->isEmpty()) {
            $this->info('No projects found to recalculate.');

            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            $projectTimeService->recalculateProject($project->id);
            $this->info("Recalculated project times for project #{$project->id} ({$project->name}).");
        }

        return self::SUCCESS;
    }
}

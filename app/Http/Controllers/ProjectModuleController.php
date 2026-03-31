<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectModuleRequest;
use App\Models\Project;
use App\Models\ProjectModule;
use Illuminate\Http\JsonResponse;

class ProjectModuleController extends Controller
{
    public function store(ProjectModuleRequest $request, Project $project): JsonResponse
    {
        $this->ensureAgileProject($project);

        $projectModule = $project->projectModules()->create($this->prepareData($request));

        return response()->json([
            'status' => true,
            'message' => 'Project module created successfully.',
            'data' => $projectModule,
        ]);
    }

    public function update(ProjectModuleRequest $request, Project $project, ProjectModule $projectModule): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectModule->project_id === $project->id, 404);

        $projectModule->update($this->prepareData($request));

        return response()->json([
            'status' => true,
            'message' => 'Project module updated successfully.',
            'data' => $projectModule,
        ]);
    }

    public function destroy(Project $project, ProjectModule $projectModule)
    {
        $this->ensureAgileProject($project);
        abort_unless($projectModule->project_id === $project->id, 404);

        $projectModule->delete();

        return redirect()
            ->route('projects.edit', $project)
            ->with('success', 'Project module deleted successfully.');
    }

    private function prepareData(ProjectModuleRequest $request): array
    {
        $data = $request->validated();
        $data['estimated_time_seconds'] = array_key_exists('estimated_time_minutes', $data) && $data['estimated_time_minutes'] !== null
            ? (int) $data['estimated_time_minutes'] * 60
            : null;

        unset($data['estimated_time_minutes']);

        return $data;
    }

    private function ensureAgileProject(Project $project): void
    {
        abort_unless($project->is_agile, 404);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectSprintRequest;
use App\Models\AgileSprint;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectSprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectSprintController extends Controller
{
    public function store(ProjectSprintRequest $request, Project $project, ProjectModule $projectModule): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectModule->project_id === $project->id, 404);

        $projectSprint = DB::transaction(function () use ($request, $project, $projectModule) {
            return $projectModule->projectSprints()->create($this->prepareData($request, [
                'project_id' => $project->id,
                'project_module_id' => $projectModule->id,
                'order' => $this->nextOrder($projectModule),
            ]));
        });

        return response()->json([
            'status' => true,
            'message' => 'Project sprint created successfully.',
            'data' => $projectSprint,
            'html' => $this->renderSection($project, $projectModule->id, $projectSprint->id),
            'render_target' => '[data-project-module-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    public function update(ProjectSprintRequest $request, Project $project, ProjectSprint $projectSprint): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectSprint->project_id === $project->id, 404);

        $targetProjectModule = ProjectModule::query()
            ->where('project_id', $project->id)
            ->findOrFail($request->integer('project_module_id'));

        $originalProjectModule = $projectSprint->projectModule;
        $moduleChanged = (int) $projectSprint->project_module_id !== (int) $targetProjectModule->id;

        DB::transaction(function () use ($request, $projectSprint, $targetProjectModule, $originalProjectModule, $moduleChanged) {
            $updateData = $this->prepareData($request, [
                'project_id' => $projectSprint->project_id,
                'project_module_id' => $targetProjectModule->id,
            ]);

            if ($moduleChanged) {
                $updateData['order'] = $this->nextOrder($targetProjectModule);
            }

            $projectSprint->update($updateData);

            if ($moduleChanged && $originalProjectModule) {
                $this->normalizeOrder($originalProjectModule);
                $originalProjectModule->refreshDerivedTimeSec();
            }

            $this->normalizeOrder($targetProjectModule);
            $targetProjectModule->refreshDerivedTimeSec();
        });

        $projectSprint->refresh();

        return response()->json([
            'status' => true,
            'message' => 'Project sprint updated successfully.',
            'data' => $projectSprint,
            'html' => $this->renderSection($project, $projectSprint->project_module_id, $projectSprint->id),
            'render_target' => '[data-project-module-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    public function reorder(Project $project, ProjectModule $projectModule, Request $request): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectModule->project_id === $project->id, 404);

        $sprintIds = $request->validate([
            'sprint_ids' => ['required', 'array', 'min:1'],
            'sprint_ids.*' => ['integer'],
        ])['sprint_ids'];

        $sprints = $projectModule->projectSprints()
            ->whereIn('id', $sprintIds)
            ->pluck('id')
            ->all();

        abort_unless(count($sprints) === $projectModule->projectSprints()->count(), 422);
        abort_unless(count(array_unique($sprintIds)) === count($sprintIds), 422);

        DB::transaction(function () use ($projectModule, $sprintIds) {
            foreach ($sprintIds as $index => $sprintId) {
                $projectModule->projectSprints()
                    ->whereKey($sprintId)
                    ->update(['order' => $index + 1]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Project sprints reordered successfully.',
        ]);
    }

    private function prepareData(ProjectSprintRequest $request, array $overrides = []): array
    {
        $data = $request->validated();
        $data['estimated_time_seconds'] = array_key_exists('estimated_time_minutes', $data) && $data['estimated_time_minutes'] !== null
            ? (int) $data['estimated_time_minutes'] * 60
            : null;

        unset($data['estimated_time_minutes']);

        return array_merge($data, $overrides);
    }

    private function ensureAgileProject(Project $project): void
    {
        abort_unless($project->is_agile, 404);
    }

    private function nextOrder(ProjectModule $projectModule): int
    {
        return ((int) $projectModule->projectSprints()->max('order')) + 1;
    }

    private function normalizeOrder(ProjectModule $projectModule): void
    {
        $projectModule->projectSprints()
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (ProjectSprint $projectSprint, int $index) {
                $projectSprint->updateQuietly([
                    'order' => $index + 1,
                ]);
            });
    }

    private function renderSection(Project $project, ?int $openModuleId = null, ?int $openSprintId = null): string
    {
        $project->load([
            'projectModules' => fn ($query) => $query
                ->with([
                    'addedBy',
                    'updatedBy',
                    'projectSprints' => fn ($sprintQuery) => $sprintQuery
                        ->with(['addedBy', 'updatedBy'])
                        ->orderBy('order')
                        ->orderBy('id'),
                ])
                ->orderBy('order')
                ->orderBy('id'),
        ]);

        return view('projects.partials.module.section', [
            'project' => $project,
            'projectModules' => $project->projectModules,
            'agileSprints' => AgileSprint::active()->orderBy('order', 'asc')->get(),
            'openModuleId' => $openModuleId,
            'openSprintId' => $openSprintId,
            'trashedProjectModules' => ProjectModule::onlyTrashed()
                ->where('project_id', $project->id)
                ->orderByDesc('deleted_at')
                ->get(),
        ])->render();
    }
}

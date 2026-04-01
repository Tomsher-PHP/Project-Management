<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectModuleRequest;
use App\Models\AgileSprint;
use App\Models\Project;
use App\Models\ProjectModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectModuleController extends Controller
{
    public function store(ProjectModuleRequest $request, Project $project): JsonResponse
    {
        $this->ensureAgileProject($project);

        $projectModule = DB::transaction(function () use ($project, $request) {
            return $project->projectModules()->create(
                $this->prepareData($request, [
                    'order' => $this->nextOrder($project),
                ])
            );
        });

        return response()->json([
            'status' => true,
            'message' => 'Project module created successfully.',
            'data' => $projectModule,
            'html' => $this->renderSection($project),
            'render_target' => '[data-project-module-section]',
            'render_mode' => 'replace_outer',
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
            'html' => $this->renderSection($project),
            'render_target' => '[data-project-module-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    public function destroy(Request $request, Project $project, ProjectModule $projectModule)
    {
        $this->ensureAgileProject($project);
        abort_unless($projectModule->project_id === $project->id, 404);

        if ($this->moduleHasSprints($projectModule)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This module cannot be deleted because it already has sprints.',
                ], 422);
            }

            return redirect()
                ->route('projects.edit', $project)
                ->with('error', 'This module cannot be deleted because it already has sprints.');
        }

        DB::transaction(function () use ($project, $projectModule) {
            $projectModule->delete();
            $this->normalizeProjectModuleOrder($project);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Project module deleted successfully.',
                'html' => $this->renderSection($project),
                'render_target' => '[data-project-module-section]',
                'render_mode' => 'replace_outer',
            ]);
        }

        return redirect()
            ->route('projects.edit', $project)
            ->with('success', 'Project module deleted successfully.');
    }

    public function reorder(Request $request, Project $project): JsonResponse
    {
        $this->ensureAgileProject($project);

        $moduleIds = $request->validate([
            'module_ids' => ['required', 'array', 'min:1'],
            'module_ids.*' => ['integer'],
        ])['module_ids'];

        $modules = $project->projectModules()
            ->whereIn('id', $moduleIds)
            ->pluck('id')
            ->all();

        abort_unless(count($modules) === $project->projectModules()->count(), 422);
        abort_unless(count(array_unique($moduleIds)) === count($moduleIds), 422);

        DB::transaction(function () use ($project, $moduleIds) {
            foreach ($moduleIds as $index => $moduleId) {
                $project->projectModules()
                    ->whereKey($moduleId)
                    ->update(['order' => $index + 1]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Project modules reordered successfully.',
        ]);
    }

    public function restore(Request $request, Project $project, int $projectModule): JsonResponse
    {
        $this->ensureAgileProject($project);

        $trashedModule = ProjectModule::onlyTrashed()
            ->where('project_id', $project->id)
            ->findOrFail($projectModule);

        $restoredName = $this->resolveRestoredName($project, $trashedModule->name, $trashedModule->id);

        DB::transaction(function () use ($project, $trashedModule, $restoredName) {
            $trashedModule->name = $restoredName;
            $trashedModule->order = $this->nextOrder($project);
            $trashedModule->updated_by = Auth::id();
            $trashedModule->restore();
            $trashedModule->saveQuietly();
        });

        $message = $restoredName === $trashedModule->getOriginal('name')
            ? 'Project module restored successfully.'
            : "Project module restored as \"{$restoredName}\" because the previous name already exists.";

        return response()->json([
            'status' => true,
            'message' => $message,
            'html' => $this->renderSection($project),
            'render_target' => '[data-project-module-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    private function prepareData(ProjectModuleRequest $request, array $overrides = []): array
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

    private function nextOrder(Project $project): int
    {
        return ((int) $project->projectModules()->max('order')) + 1;
    }

    private function normalizeProjectModuleOrder(Project $project): void
    {
        $project->projectModules()
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->each(function (ProjectModule $module, int $index) {
                $module->updateQuietly(['order' => $index + 1]);
            });
    }

    private function renderSection(Project $project): string
    {
        $project->load([
            'projectModules' => fn ($query) => $query
                ->with(['addedBy', 'updatedBy'])
                ->orderBy('order')
                ->orderBy('id'),
        ]);

        return view('projects.partials.module.section', [
            'project' => $project,
            'projectModules' => $project->projectModules,
            'agileSprints' => AgileSprint::active()->orderBy('order', 'asc')->get(),
            'trashedProjectModules' => ProjectModule::onlyTrashed()
                ->where('project_id', $project->id)
                ->orderByDesc('deleted_at')
                ->get(),
        ])->render();
    }

    private function moduleHasSprints(ProjectModule $projectModule): bool
    {
        if (!Schema::hasTable('project_sprints')) {
            return false;
        }

        $query = DB::table('project_sprints')
            ->where('project_module_id', $projectModule->id);

        if (Schema::hasColumn('project_sprints', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    private function resolveRestoredName(Project $project, string $originalName, int $restoringModuleId): string
    {
        $candidate = $originalName;
        $suffix = 1;

        while ($project->projectModules()
            ->where('name', $candidate)
            ->whereKeyNot($restoringModuleId)
            ->exists()) {
            $candidate = $suffix === 1
                ? "{$originalName} (Restored)"
                : "{$originalName} (Restored {$suffix})";

            $suffix++;
        }

        return $candidate;
    }
}

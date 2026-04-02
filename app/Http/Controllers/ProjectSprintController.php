<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectSprintRequest;
use App\Models\AgileSprint;
use App\Models\AgileSprintStatus;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectSprint;
use App\Models\ProjectStatus;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectSprintController extends Controller
{
    public function index(Project $project, ProjectModule $projectModule): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectModule->project_id === $project->id, 404);

        $projectSprints = $projectModule->projectSprints()
            ->with(['addedBy', 'updatedBy', 'status'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => true,
            'module' => [
                'id' => $projectModule->id,
                'name' => $projectModule->name,
            ],
            'count' => $projectSprints->count(),
            'sprints' => $projectSprints
                ->map(fn (ProjectSprint $projectSprint) => $this->serializeSprint($projectSprint))
                ->values(),
            'html' => view('projects.partials.module.sprints', [
                'project' => $project,
                'module' => $projectModule,
                'projectSprints' => $projectSprints,
            ])->render(),
        ]);
    }

    public function store(ProjectSprintRequest $request, Project $project, ProjectModule $projectModule): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectModule->project_id === $project->id, 404);

        $projectSprint = DB::transaction(function () use ($request, $project, $projectModule) {
            return $projectModule->projectSprints()->create($this->prepareData($request, [
                'project_id' => $project->id,
                'project_module_id' => $projectModule->id,
                'sort_order' => $this->nextOrder($projectModule),
            ], true));
        });

        return response()->json([
            'status' => true,
            'message' => 'Project sprint created successfully.',
            'data' => $projectSprint,
            'sprint' => $this->serializeSprint($projectSprint),
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
                $updateData['sort_order'] = $this->nextOrder($targetProjectModule);
            }

            $projectSprint->update($updateData);

            if ($moduleChanged && $originalProjectModule) {
                $this->normalizeOrder($originalProjectModule);
                $originalProjectModule->refreshTrackedTimeMetrics();
            }

            $this->normalizeOrder($targetProjectModule);
            $targetProjectModule->refreshTrackedTimeMetrics();
        });

        $projectSprint->refresh();

        return response()->json([
            'status' => true,
            'message' => 'Project sprint updated successfully.',
            'data' => $projectSprint,
            'sprint' => $this->serializeSprint($projectSprint),
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
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Project sprints reordered successfully.',
        ]);
    }

    private function prepareData(ProjectSprintRequest $request, array $overrides = [], bool $applyDefaultStatus = false): array
    {
        $data = $request->validated();

        if ($applyDefaultStatus && empty($data['status_id'])) {
            $data['status_id'] = AgileSprintStatus::query()
                ->where('is_default', true)
                ->value('id');
        }

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
        return ((int) $projectModule->projectSprints()->max('sort_order')) + 1;
    }

    private function normalizeOrder(ProjectModule $projectModule): void
    {
        $projectModule->projectSprints()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (ProjectSprint $projectSprint, int $index) {
                $projectSprint->updateQuietly([
                    'sort_order' => $index + 1,
                ]);
            });
    }

    private function serializeSprint(ProjectSprint $projectSprint): array
    {
        return [
            'id' => $projectSprint->id,
            'project_module_id' => $projectSprint->project_module_id,
            'name' => $projectSprint->name,
            'color' => $projectSprint->color,
            'description' => $projectSprint->description,
            'status_id' => $projectSprint->status_id,
            'start_date' => $projectSprint->start_date?->format('Y-m-d'),
            'end_date' => $projectSprint->end_date?->format('Y-m-d'),
            'estimated_time_minutes' => $projectSprint->estimated_time_minutes,
            'sort_order' => $projectSprint->sort_order,
        ];
    }

    private function renderSection(Project $project, ?int $openModuleId = null, ?int $openSprintId = null): string
    {
        $project->load([
            'projectModules' => fn ($query) => $query
                ->with([
                    'addedBy',
                    'updatedBy',
                    'status',
                    'owner',
                ])
                ->withCount('projectSprints')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        return view('projects.partials.module.section', [
            'project' => $project,
            'projectModules' => $project->projectModules,
            'agileSprints' => AgileSprint::active()->orderBy('sort_order', 'asc')->get(),
            'projectStatuses' => ProjectStatus::active()->orderBy('sort_order', 'asc')->get(),
            'assignableUsers' => app(UserService::class)->getAccessibleUsers(auth()->user()),
            'openModuleId' => $openModuleId,
            'openSprintId' => $openSprintId,
            'trashedProjectModules' => ProjectModule::onlyTrashed()
                ->where('project_id', $project->id)
                ->orderByDesc('deleted_at')
                ->get(),
        ])->render();
    }
}

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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectSprintController extends Controller
{
    private const SPRINTS_PER_PAGE = 5;

    public function index(Project $project, ProjectModule $projectModule): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectModule->project_id === $project->id, 404);

        $loadAll = request()->boolean('all');
        $page = max((int) request()->integer('page', 1), 1);
        $perPage = self::SPRINTS_PER_PAGE;
        $sprintsQuery = $projectModule->projectSprints()
            ->with(['addedBy', 'updatedBy', 'status'])
            ->orderForDisplay();
        $totalCount = (clone $sprintsQuery)->count();
        $lastPage = max((int) ceil($totalCount / $perPage), 1);

        if ($loadAll) {
            $page = 1;
            $projectSprints = $sprintsQuery->get();
        } else {
            $page = min($page, $lastPage);
            $projectSprints = $sprintsQuery
                ->forPage($page, $perPage)
                ->get();
        }

        $hasMorePages = ! $loadAll && $page < $lastPage;
        $pagination = [
            'page' => $page,
            'per_page' => $loadAll ? max($totalCount, 1) : $perPage,
            'total' => $totalCount,
            'last_page' => $loadAll ? 1 : $lastPage,
            'next_page' => $hasMorePages ? $page + 1 : null,
            'has_more_pages' => $hasMorePages,
            'all_pages_loaded' => $loadAll || ! $hasMorePages,
            'load_all' => $loadAll,
        ];

        return response()->json([
            'status' => true,
            'module' => [
                'id' => $projectModule->id,
                'name' => $projectModule->name,
            ],
            'count' => $totalCount,
            'sprints' => $projectSprints
                ->map(fn (ProjectSprint $projectSprint) => $this->serializeSprint($projectSprint))
                ->values(),
            'pagination' => $pagination,
            'html' => view('projects.partials.module.sprints', [
                'project' => $project,
                'module' => $projectModule,
                'projectSprints' => $projectSprints,
                'pagination' => $pagination,
            ])->render(),
            'items_html' => view('projects.partials.module.sprint-cards', [
                'project' => $project,
                'module' => $projectModule,
                'projectSprints' => $projectSprints,
                'allPagesLoaded' => $pagination['all_pages_loaded'],
                'showEmptyState' => false,
            ])->render(),
        ]);
    }

    public function store(ProjectSprintRequest $request, Project $project, ProjectModule $projectModule): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectModule->project_id === $project->id, 404);

        if ($response = $this->validateBacklogModulePlacement(null, $projectModule)) {
            return $response;
        }

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

        if ($response = $this->validateBacklogModulePlacement($projectSprint, $targetProjectModule)) {
            return $response;
        }

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

    public function destroy(Request $request, Project $project, ProjectSprint $projectSprint)
    {
        $this->ensureAgileProject($project);
        abort_unless($projectSprint->project_id === $project->id, 404);

        if ($this->isProtectedProjectSprint($projectSprint)) {
            $message = $projectSprint->is_backlog
                ? 'The project backlog sprint cannot be deleted.'
                : 'System project sprints cannot be deleted.';

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('projects.edit', $project)
                ->with('error', $message);
        }

        if ($this->sprintHasTasks($projectSprint)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This sprint cannot be deleted because it already has tasks.',
                ], 422);
            }

            return redirect()
                ->route('projects.edit', $project)
                ->with('error', 'This sprint cannot be deleted because it already has tasks.');
        }

        $projectModule = $projectSprint->projectModule;

        DB::transaction(function () use ($projectSprint, $projectModule) {
            $projectSprint->delete();

            if ($projectModule) {
                $this->normalizeOrder($projectModule);
                $projectModule->refreshTrackedTimeMetrics();
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Project sprint deleted successfully.',
                'html' => $this->renderSection($project, $projectModule?->id),
                'render_target' => '[data-project-module-section]',
                'render_mode' => 'replace_outer',
            ]);
        }

        return redirect()
            ->route('projects.edit', $project)
            ->with('success', 'Project sprint deleted successfully.');
    }

    public function restore(Request $request, Project $project, int $projectSprint): JsonResponse
    {
        $this->ensureAgileProject($project);

        $trashedSprint = ProjectSprint::onlyTrashed()
            ->where('project_id', $project->id)
            ->findOrFail($projectSprint);

        $projectModule = ProjectModule::query()
            ->where('project_id', $project->id)
            ->findOrFail($trashedSprint->project_module_id);

        if ($response = $this->validateBacklogModulePlacement($trashedSprint, $projectModule)) {
            return $response;
        }

        $restoredName = $this->resolveRestoredName($projectModule, $trashedSprint->name, $trashedSprint->id);

        DB::transaction(function () use ($trashedSprint, $projectModule, $restoredName) {
            $trashedSprint->name = $restoredName;
            $trashedSprint->sort_order = $this->nextOrder($projectModule);
            $trashedSprint->updated_by = Auth::id();
            $trashedSprint->restore();
            $trashedSprint->saveQuietly();
        });

        $message = $restoredName === $trashedSprint->getOriginal('name')
            ? 'Project sprint restored successfully.'
            : "Project sprint restored as \"{$restoredName}\" because the previous name already exists.";

        return response()->json([
            'status' => true,
            'message' => $message,
            'html' => $this->renderSection($project, $projectModule->id, $trashedSprint->id),
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

    private function isProtectedProjectSprint(ProjectSprint $projectSprint): bool
    {
        return (bool) ($projectSprint->is_backlog || $projectSprint->is_system);
    }

    private function validateBacklogModulePlacement(?ProjectSprint $projectSprint, ProjectModule $targetProjectModule): ?JsonResponse
    {
        if ($projectSprint?->is_backlog && (int) $projectSprint->project_module_id !== (int) $targetProjectModule->id) {
            return response()->json([
                'status' => false,
                'message' => 'The project backlog sprint must remain in the project backlog module.',
            ], 422);
        }

        if ($targetProjectModule->is_backlog && ! $projectSprint?->is_backlog) {
            return response()->json([
                'status' => false,
                'message' => 'The project backlog module can only contain the backlog sprint.',
            ], 422);
        }

        return null;
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

    private function sprintHasTasks(ProjectSprint $projectSprint): bool
    {
        if (!Schema::hasTable('tasks') || !Schema::hasColumn('tasks', 'project_sprint_id')) {
            return false;
        }

        $query = DB::table('tasks')
            ->where('project_sprint_id', $projectSprint->id);

        if (Schema::hasColumn('tasks', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    private function serializeSprint(ProjectSprint $projectSprint): array
    {
        return [
            'id' => $projectSprint->id,
            'project_module_id' => $projectSprint->project_module_id,
            'name' => $projectSprint->name,
            'color' => $projectSprint->color,
            'description' => $projectSprint->description,
            'task_count' => $projectSprint->task_count,
            'status_id' => $projectSprint->status_id,
            'start_date' => $projectSprint->start_date?->format('Y-m-d'),
            'end_date' => $projectSprint->end_date?->format('Y-m-d'),
            'estimated_time_minutes' => $projectSprint->estimated_time_minutes,
            'sort_order' => $projectSprint->sort_order,
        ];
    }

    private function resolveRestoredName(ProjectModule $projectModule, string $originalName, int $restoringSprintId): string
    {
        $candidate = $originalName;
        $suffix = 1;

        while ($projectModule->projectSprints()
            ->where('name', $candidate)
            ->whereKeyNot($restoringSprintId)
            ->exists()) {
            $candidate = $suffix === 1
                ? "{$originalName} (Restored)"
                : "{$originalName} (Restored {$suffix})";

            $suffix++;
        }

        return $candidate;
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
            'trashedProjectSprintsByModule' => ProjectSprint::onlyTrashed()
                ->where('project_id', $project->id)
                ->orderByDesc('deleted_at')
                ->get()
                ->groupBy('project_module_id'),
        ])->render();
    }
}

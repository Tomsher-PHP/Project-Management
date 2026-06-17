<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectSprintRequest;
use App\Models\AgileSprint;
use App\Models\AgileSprintStatus;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\ProjectStatus;
use App\Services\NotificationService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectSprintController extends Controller
{
    private const SPRINTS_PER_PAGE = 5;

    public function index(Project $project, ProjectMilestone $projectMilestone): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectMilestone->project_id === $project->id, 404);

        $loadAll = request()->boolean('all');
        $page = max((int) request()->integer('page', 1), 1);
        $perPage = self::SPRINTS_PER_PAGE;
        $sprintsQuery = $projectMilestone->projectSprints()
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
            'milestone' => [
                'id' => $projectMilestone->id,
                'name' => $projectMilestone->name,
            ],
            'count' => $totalCount,
            'sprints' => $projectSprints
                ->map(fn (ProjectSprint $projectSprint) => $this->serializeSprint($projectSprint))
                ->values(),
            'pagination' => $pagination,
            'html' => view('projects.partials.milestone.sprints', [
                'project' => $project,
                'milestone' => $projectMilestone,
                'projectSprints' => $projectSprints,
                'pagination' => $pagination,
            ])->render(),
            'items_html' => view('projects.partials.milestone.sprint-cards', [
                'project' => $project,
                'milestone' => $projectMilestone,
                'projectSprints' => $projectSprints,
                'allPagesLoaded' => $pagination['all_pages_loaded'],
                'showEmptyState' => false,
            ])->render(),
        ]);
    }

    public function store(ProjectSprintRequest $request, Project $project, ProjectMilestone $projectMilestone): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectMilestone->project_id === $project->id, 404);

        if ($response = $this->validateBacklogMilestonePlacement(null, $projectMilestone)) {
            return $response;
        }

        $projectSprint = DB::transaction(function () use ($request, $project, $projectMilestone) {
            return $projectMilestone->projectSprints()->create($this->prepareData($request, [
                'project_id' => $project->id,
                'project_milestone_id' => $projectMilestone->id,
                'sort_order' => $this->nextOrder($projectMilestone),
            ], true));
        });

        return response()->json([
            'status' => true,
            'message' => 'Project sprint created successfully.',
            'data' => $projectSprint,
            'sprint' => $this->serializeSprint($projectSprint),
            'html' => $this->renderSection($project, $projectMilestone->id, $projectSprint->id),
            'render_target' => '[data-project-milestone-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    public function update(ProjectSprintRequest $request, Project $project, ProjectSprint $projectSprint): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectSprint->project_id === $project->id, 404);

        $targetProjectMilestone = ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->findOrFail($request->integer('project_milestone_id'));

        if ($response = $this->validateBacklogMilestonePlacement($projectSprint, $targetProjectMilestone)) {
            return $response;
        }

        $originalProjectMilestone = $projectSprint->projectMilestone;
        $milestoneChanged = (int) $projectSprint->project_milestone_id !== (int) $targetProjectMilestone->id;
        $originalTimelineValues = $projectSprint->only([
            'estimated_time_seconds',
            'start_date',
            'end_date',
        ]);

        DB::transaction(function () use ($request, $projectSprint, $targetProjectMilestone, $originalProjectMilestone, $milestoneChanged) {
            $updateData = $this->prepareData($request, [
                'project_id' => $projectSprint->project_id,
                'project_milestone_id' => $targetProjectMilestone->id,
            ]);

            if ($milestoneChanged) {
                $updateData['sort_order'] = $this->nextOrder($targetProjectMilestone);
            }

            $projectSprint->update($updateData);

            if ($milestoneChanged && $originalProjectMilestone) {
                $this->normalizeOrder($originalProjectMilestone);
            }

            $this->normalizeOrder($targetProjectMilestone);
        });

        $projectSprint->refresh();

        if ($actor = $request->user()) {
            app(NotificationService::class)->notifySprintTimelineChanged(
                $projectSprint,
                $actor,
                $originalTimelineValues
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Project sprint updated successfully.',
            'data' => $projectSprint,
            'sprint' => $this->serializeSprint($projectSprint),
            'html' => $this->renderSection($project, $projectSprint->project_milestone_id, $projectSprint->id),
            'render_target' => '[data-project-milestone-section]',
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

        $projectMilestone = $projectSprint->projectMilestone;

        DB::transaction(function () use ($projectSprint, $projectMilestone) {
            $projectSprint->delete();

            if ($projectMilestone) {
                $this->normalizeOrder($projectMilestone);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Project sprint deleted successfully.',
                'html' => $this->renderSection($project, $projectMilestone?->id),
                'render_target' => '[data-project-milestone-section]',
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

        $projectMilestone = ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->findOrFail($trashedSprint->project_milestone_id);

        if ($response = $this->validateBacklogMilestonePlacement($trashedSprint, $projectMilestone)) {
            return $response;
        }

        $restoredName = $this->resolveRestoredName($projectMilestone, $trashedSprint->name, $trashedSprint->id);

        DB::transaction(function () use ($trashedSprint, $projectMilestone, $restoredName) {
            $trashedSprint->name = $restoredName;
            $trashedSprint->sort_order = $this->nextOrder($projectMilestone);
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
            'html' => $this->renderSection($project, $projectMilestone->id, $trashedSprint->id),
            'render_target' => '[data-project-milestone-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    public function reorder(Project $project, ProjectMilestone $projectMilestone, Request $request): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectMilestone->project_id === $project->id, 404);

        $sprintIds = $request->validate([
            'sprint_ids' => ['required', 'array', 'min:1'],
            'sprint_ids.*' => ['integer'],
        ])['sprint_ids'];

        $sprints = $projectMilestone->projectSprints()
            ->whereIn('id', $sprintIds)
            ->pluck('id')
            ->all();

        abort_unless(count($sprints) === $projectMilestone->projectSprints()->count(), 422);
        abort_unless(count(array_unique($sprintIds)) === count($sprintIds), 422);

        DB::transaction(function () use ($projectMilestone, $sprintIds) {
            foreach ($sprintIds as $index => $sprintId) {
                $projectMilestone->projectSprints()
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

    private function validateBacklogMilestonePlacement(?ProjectSprint $projectSprint, ProjectMilestone $targetProjectMilestone): ?JsonResponse
    {
        if ($projectSprint?->is_backlog && (int) $projectSprint->project_milestone_id !== (int) $targetProjectMilestone->id) {
            return response()->json([
                'status' => false,
                'message' => 'The project backlog sprint must remain in the project backlog milestone.',
            ], 422);
        }

        if ($targetProjectMilestone->is_backlog && ! $projectSprint?->is_backlog) {
            return response()->json([
                'status' => false,
                'message' => 'The project backlog milestone can only contain the backlog sprint.',
            ], 422);
        }

        return null;
    }

    private function ensureAgileProject(Project $project): void
    {
        abort_unless($project->is_agile, 404);
    }

    private function nextOrder(ProjectMilestone $projectMilestone): int
    {
        return ((int) $projectMilestone->projectSprints()->max('sort_order')) + 1;
    }

    private function normalizeOrder(ProjectMilestone $projectMilestone): void
    {
        $projectMilestone->projectSprints()
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
            'project_milestone_id' => $projectSprint->project_milestone_id,
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

    private function resolveRestoredName(ProjectMilestone $projectMilestone, string $originalName, int $restoringSprintId): string
    {
        $candidate = $originalName;
        $suffix = 1;

        while ($projectMilestone->projectSprints()
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

    private function renderSection(Project $project, ?int $openMilestoneId = null, ?int $openSprintId = null): string
    {
        $project->load([
            'projectMilestones' => fn ($query) => $query
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

        return view('projects.partials.milestone.section', [
            'project' => $project,
            'projectMilestones' => $project->projectMilestones,
            'agileSprints' => AgileSprint::active()->orderBy('sort_order', 'asc')->get(),
            'projectStatuses' => ProjectStatus::active()->orderBy('sort_order', 'asc')->get(),
            'assignableUsers' => app(UserService::class)->getAccessibleUsers(auth()->user()),
            'openMilestoneId' => $openMilestoneId,
            'openSprintId' => $openSprintId,
            'trashedProjectMilestones' => ProjectMilestone::onlyTrashed()
                ->where('project_id', $project->id)
                ->orderByDesc('deleted_at')
                ->get(),
            'trashedProjectSprintsByMilestone' => ProjectSprint::onlyTrashed()
                ->where('project_id', $project->id)
                ->orderByDesc('deleted_at')
                ->get()
                ->groupBy('project_milestone_id'),
        ])->render();
    }
}

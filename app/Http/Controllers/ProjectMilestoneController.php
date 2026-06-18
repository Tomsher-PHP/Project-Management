<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectMilestoneRequest;
use App\Models\AgileMilestoneStatus;
use App\Models\AgileSprint;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Services\NotificationService;
use App\Services\ProjectTimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectMilestoneController extends Controller
{
    public function store(ProjectMilestoneRequest $request, Project $project): JsonResponse
    {
        $this->ensureAgileProject($project);

        $projectMilestone = DB::transaction(function () use ($project, $request) {
            $projectMilestone = $project->projectMilestones()->create(
                $this->prepareData($request, [
                    'sort_order' => $this->nextOrder($project),
                ], true)
            );

            app(ProjectTimeService::class)->recalculateMilestoneTimes($projectMilestone->id);

            return $projectMilestone;
        });

        return response()->json([
            'status' => true,
            'message' => 'Project milestone created successfully.',
            'data' => $projectMilestone,
            'milestone' => $this->serializeMilestone($projectMilestone),
            'html' => $this->renderSection($project),
            'render_target' => '[data-project-milestone-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    public function update(ProjectMilestoneRequest $request, Project $project, ProjectMilestone $projectMilestone): JsonResponse
    {
        $this->ensureAgileProject($project);
        abort_unless($projectMilestone->project_id === $project->id, 404);

        $originalTimelineValues = $projectMilestone->only([
            'owner_id',
            'estimated_time_seconds',
            'start_date',
            'end_date',
        ]);

        $projectMilestone->update($this->prepareData($request));
        $projectMilestone->refresh();

        if ($actor = $request->user()) {
            app(NotificationService::class)->notifyMilestoneTimelineChanged(
                $projectMilestone,
                $actor,
                $originalTimelineValues
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Project milestone updated successfully.',
            'data' => $projectMilestone,
            'milestone' => $this->serializeMilestone($projectMilestone),
            'html' => $this->renderSection($project),
            'render_target' => '[data-project-milestone-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    public function destroy(Request $request, Project $project, ProjectMilestone $projectMilestone)
    {
        $this->ensureAgileProject($project);
        abort_unless($projectMilestone->project_id === $project->id, 404);

        if ($this->isProtectedProjectMilestone($projectMilestone)) {
            $message = $projectMilestone->is_backlog
                ? 'The project backlog milestone cannot be deleted.'
                : 'System project milestones cannot be deleted.';

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

        if ($this->milestoneHasSprints($projectMilestone)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This milestone cannot be deleted because it already has sprints.',
                ], 422);
            }

            return redirect()
                ->route('projects.edit', $project)
                ->with('error', 'This milestone cannot be deleted because it already has sprints.');
        }

        DB::transaction(function () use ($project, $projectMilestone) {
            $projectMilestone->delete();
            $this->normalizeProjectMilestoneOrder($project);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Project milestone deleted successfully.',
                'html' => $this->renderSection($project),
                'render_target' => '[data-project-milestone-section]',
                'render_mode' => 'replace_outer',
            ]);
        }

        return redirect()
            ->route('projects.edit', $project)
            ->with('success', 'Project milestone deleted successfully.');
    }

    public function reorder(Request $request, Project $project): JsonResponse
    {
        $this->ensureAgileProject($project);

        $milestoneIds = $request->validate([
            'milestone_ids' => ['required', 'array', 'min:1'],
            'milestone_ids.*' => ['integer'],
        ])['milestone_ids'];

        $submittedMilestoneIds = array_values(array_map('intval', $milestoneIds));
        $uniqueSubmittedMilestoneIds = array_values(array_unique($submittedMilestoneIds));
        $allMilestoneIds = $project->projectMilestones()
            ->whereIn('id', $milestoneIds)
            ->pluck('id')
            ->all();
        $reorderableMilestoneIds = $project->projectMilestones()
            ->where('is_backlog', false)
            ->where('is_system', false)
            ->pluck('id')
            ->all();

        abort_unless(count($allMilestoneIds) === count($submittedMilestoneIds), 422);
        abort_unless(count($uniqueSubmittedMilestoneIds) === count($submittedMilestoneIds), 422);

        $matchesAllMilestones = $this->hasSameOrderedIds($submittedMilestoneIds, $allMilestoneIds);
        $matchesReorderableMilestones = $this->hasSameOrderedIds($submittedMilestoneIds, $reorderableMilestoneIds);

        abort_unless($matchesAllMilestones || $matchesReorderableMilestones, 422);

        DB::transaction(function () use ($project, $submittedMilestoneIds) {
            foreach ($submittedMilestoneIds as $index => $milestoneId) {
                $project->projectMilestones()
                    ->whereKey($milestoneId)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Project milestones reordered successfully.',
            'html' => $this->renderSection($project),
            'render_target' => '[data-project-milestone-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    public function restore(Request $request, Project $project, int $projectMilestone): JsonResponse
    {
        $this->ensureAgileProject($project);

        $trashedMilestone = ProjectMilestone::onlyTrashed()
            ->where('project_id', $project->id)
            ->findOrFail($projectMilestone);

        $restoredName = $this->resolveRestoredName($project, $trashedMilestone->name, $trashedMilestone->id);

        DB::transaction(function () use ($project, $trashedMilestone, $restoredName) {
            $trashedMilestone->name = $restoredName;
            $trashedMilestone->sort_order = $this->nextOrder($project);
            $trashedMilestone->updated_by = Auth::id();
            $trashedMilestone->restore();
            $trashedMilestone->saveQuietly();
        });

        $message = $restoredName === $trashedMilestone->getOriginal('name')
            ? 'Project milestone restored successfully.'
            : "Project milestone restored as \"{$restoredName}\" because the previous name already exists.";

        return response()->json([
            'status' => true,
            'message' => $message,
            'html' => $this->renderSection($project),
            'render_target' => '[data-project-milestone-section]',
            'render_mode' => 'replace_outer',
        ]);
    }

    private function prepareData(ProjectMilestoneRequest $request, array $overrides = [], bool $applyDefaultStatus = false): array
    {
        $data = $request->validated();

        if ($applyDefaultStatus && empty($data['status_id'])) {
            $data['status_id'] = AgileMilestoneStatus::query()
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

    private function nextOrder(Project $project): int
    {
        return ((int) $project->projectMilestones()->max('sort_order')) + 1;
    }

    private function normalizeProjectMilestoneOrder(Project $project): void
    {
        $project->projectMilestones()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function (ProjectMilestone $milestone, int $index) {
                $milestone->updateQuietly(['sort_order' => $index + 1]);
            });
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
                ->orderForDisplay(),
        ]);

        return view('projects.partials.milestone.section', [
            'project' => $project,
            'projectMilestones' => $project->projectMilestones,
            'agileSprints' => AgileSprint::active()->orderBy('sort_order', 'asc')->get(),
            'agileMilestoneStatuses' => AgileMilestoneStatus::active()->orderBy('sort_order', 'asc')->get(),
            'assignableUsers' => $project->activeMembers()
                ->orderBy('users.name')
                ->get(['users.id', 'users.name']),
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

    private function milestoneHasSprints(ProjectMilestone $projectMilestone): bool
    {
        if (!Schema::hasTable('project_sprints')) {
            return false;
        }

        $query = DB::table('project_sprints')
            ->where('project_milestone_id', $projectMilestone->id);

        if (Schema::hasColumn('project_sprints', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    private function isProtectedProjectMilestone(ProjectMilestone $projectMilestone): bool
    {
        return (bool) ($projectMilestone->is_backlog || $projectMilestone->is_system);
    }

    private function resolveRestoredName(Project $project, string $originalName, int $restoringMilestoneId): string
    {
        $candidate = $originalName;
        $suffix = 1;

        while ($project->projectMilestones()
            ->where('name', $candidate)
            ->whereKeyNot($restoringMilestoneId)
            ->exists()) {
            $candidate = $suffix === 1
                ? "{$originalName} (Restored)"
                : "{$originalName} (Restored {$suffix})";

            $suffix++;
        }

        return $candidate;
    }

    private function hasSameOrderedIds(array $submittedIds, array $expectedIds): bool
    {
        $normalizedExpectedIds = array_values(array_map('intval', $expectedIds));
        sort($submittedIds);
        sort($normalizedExpectedIds);

        return $submittedIds === $normalizedExpectedIds;
    }

    private function serializeMilestone(ProjectMilestone $projectMilestone): array
    {
        $projectMilestone->loadMissing(['status', 'owner']);

        return [
            'id' => $projectMilestone->id,
            'name' => $projectMilestone->name,
            'color' => $projectMilestone->color,
            'description' => $projectMilestone->description,
            'status_id' => $projectMilestone->status_id,
            'owner_id' => $projectMilestone->owner_id,
            'start_date' => $projectMilestone->start_date?->format('Y-m-d'),
            'end_date' => $projectMilestone->end_date?->format('Y-m-d'),
            'completed_at' => $projectMilestone->completed_at?->format('Y-m-d\TH:i'),
            'estimated_time_minutes' => $projectMilestone->estimated_time_minutes,
            'sort_order' => $projectMilestone->sort_order,
            'status_name' => $projectMilestone->status?->name,
            'owner_name' => $projectMilestone->owner?->name,
        ];
    }
}

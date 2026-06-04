<?php

namespace App\Services;

use App\Http\Controllers\Concerns\BuildsProjectActivityQueries;
use App\Http\Controllers\ProjectChecklistController;
use App\Http\Controllers\ProjectPaymentController;
use App\Http\Controllers\ProjectTaskController;
use App\Models\AgileMilestone;
use App\Models\AgileMilestoneStatus;
use App\Models\AgileSprint;
use App\Models\Project;
use App\Models\Customer;
use App\Models\ProjectCategory;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Technology;
use App\Providers\AppServiceProvider;
use App\Traits\ProjectHeaderTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProjectRestoreService
{
    use BuildsProjectActivityQueries;
    use ProjectHeaderTrait;

    protected ProjectPaymentServices $projectPaymentService;
    protected ProjectServices $projectServices;
    protected ProjectAnalyticsService $analyticsService;
    protected UserService $userService;

    public function __construct(
        ProjectPaymentServices $projectPaymentService,
        ProjectServices $projectServices,
        ProjectAnalyticsService $analyticsService,
        UserService $userService
    ) {
        $this->projectPaymentService = $projectPaymentService;
        $this->projectServices = $projectServices;
        $this->analyticsService = $analyticsService;
        $this->userService = $userService;
    }

    /**
     * Get paginated soft-deleted projects.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getDeletedProjects($perPage = 10)
    {
        return Project::onlyTrashed()
            ->accessibleBy(auth()->user())
            ->filter(request()->all())
            ->sort(request()->all())
            ->with(['customer', 'addedBy', 'teamLeader'])
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findDeletedProjectOrFail(int $id): Project
    {
        return Project::withTrashed()->findOrFail($id);
    }

    public function assertDeletedProject(Project $project): void
    {
        abort_unless($project->trashed(), 404);
    }

    public function getDeletedProjectDetailData(Project $project): array
    {
        $this->assertDeletedProject($project);

        return array_merge([
            'project' => $project,
            'projectActivitiesCount' => $this->getProjectActivitiesQuery($project)->count(),
            'projectCommentsCount' => $project->comments()->count(),
        ], $this->getProjectHeaderData($project));
    }

    public function renderDeletedProjectTab(Project $project, string $tab, Request $request): string
    {
        $this->assertDeletedProject($project);

        return match ($tab) {
            'overview' => $this->renderOverviewTab($project),
            'milestones' => $this->renderMilestonesTab($project),
            'tasks' => app(ProjectTaskController::class)->renderTasksTab($project),
            'team' => $this->renderTeamTab($project),
            'scope' => $this->renderScopeTab($project),
            'notes' => $this->renderNotesTab($project, $request),
            'history' => $this->renderHistoryTab($project),
            'settings' => $this->renderSettingsTab($project),
            'payments' => app(ProjectPaymentController::class)->renderPaymentsTab($project),
            'checklists' => app(ProjectChecklistController::class)->renderChecklistsTab($project),
            default => abort(404),
        };
    }

    public function getRecentProjectComments(Project $project, int $limit = 10): Collection
    {
        $this->assertDeletedProject($project);

        return $project->comments()
            ->with('user.primaryAttachment')
            ->latest()
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();
    }

    /**
     * Validate whether a deleted project can be restored.
     *
     * @param Project $project
     * @return array
     */
    public function validateRestore(Project $project): array
    {
        // 1. Customer Check: Block if related customer is deleted/missing
        if ($project->customer_id) {
            $customerExists = Customer::withTrashed()->where('id', $project->customer_id)->exists();
            if (!$customerExists) {
                return [
                    'can_restore' => false,
                    'message' => 'Cannot restore this project because the related customer is deleted or missing. Restore the customer first.',
                ];
            }
        }

        // 2. Project Code uniqueness (since project_code unique constraint exists)
        if ($project->project_code) {
            $codeInUse = Project::where('project_code', $project->project_code)->exists();
            if ($codeInUse) {
                return [
                    'can_restore' => false,
                    'message' => 'Cannot restore this project because another active project already uses the same project code.',
                ];
            }
        }

        return [
            'can_restore' => true,
            'message' => null,
        ];
    }

    /**
     * Bulk restore soft-deleted projects.
     *
     * @param array $projectIds
     * @return array
     */
    public function bulkRestoreProjects(array $projectIds): array
    {
        $projects = Project::onlyTrashed()
            ->whereIn('id', $projectIds)
            ->get();

        if ($projects->isEmpty()) {
            return [
                'selected_count' => 0,
                'restored_count' => 0,
                'failed_count' => 0,
                'failed_details' => [],
            ];
        }

        $restoredCount = 0;
        $failedCount = 0;
        $failedDetails = [];

        foreach ($projects as $project) {
            $validation = $this->validateRestore($project);

            if (!$validation['can_restore']) {
                $failedCount++;
                $failedDetails[] = "Project '{$project->name}': {$validation['message']}";
                continue;
            }

            // Restore ONLY the project
            $project->restore();
            $restoredCount++;
        }

        return [
            'selected_count' => $projects->count(),
            'restored_count' => $restoredCount,
            'failed_count' => $failedCount,
            'failed_details' => $failedDetails,
        ];
    }

    private function renderOverviewTab(Project $project): string
    {
        $progressbar = $this->analyticsService->getProgressbar($project);
        $taskStatusOverview = $this->analyticsService->getTaskStatusOverview($project);
        $taskAssigneeOverview = $this->analyticsService->getTaskAssigneeOverview($project);
        $milestoneBurnupChart = $this->analyticsService->getMilestoneBurnupChartData($project);

        return view('projects.partials.tabs.overview', [
            'project' => $project,
            'progressbar' => $progressbar,
            'taskStatusOverview' => $taskStatusOverview,
            'taskAssigneeOverview' => $taskAssigneeOverview,
            'milestoneBurnupChart' => $milestoneBurnupChart,
            'totalTaskCount' => $taskStatusOverview->sum('count'),
        ])->render();
    }

    private function renderMilestonesTab(Project $project): string
    {
        $projectMilestones = $project->projectMilestones()
            ->with([
                'addedBy',
                'updatedBy',
                'status',
                'owner',
            ])
            ->withCount('projectSprints')
            ->orderForDisplay()
            ->get();

        $agileMilestones = AgileMilestone::active()->orderBy('sort_order', 'asc')->get();
        $agileSprints = AgileSprint::active()->orderBy('sort_order', 'asc')->get();
        $agileMilestoneStatuses = AgileMilestoneStatus::active()->orderBy('sort_order', 'asc')->get();
        $assignableUsers = $project->activeMembers()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);
        $trashedProjectMilestones = ProjectMilestone::onlyTrashed()
            ->where('project_id', $project->id)
            ->orderByDesc('deleted_at')
            ->get();
        $trashedProjectSprints = ProjectSprint::onlyTrashed()
            ->where('project_id', $project->id)
            ->orderByDesc('deleted_at')
            ->get()
            ->groupBy('project_milestone_id');

        return view('projects.partials.tabs.milestones', [
            'project' => $project,
            'projectMilestones' => $projectMilestones,
            'agileMilestones' => $agileMilestones,
            'agileSprints' => $agileSprints,
            'agileMilestoneStatuses' => $agileMilestoneStatuses,
            'assignableUsers' => $assignableUsers,
            'trashedProjectMilestones' => $trashedProjectMilestones,
            'trashedProjectSprintsByMilestone' => $trashedProjectSprints,
            'trashedProjectSprintsByModule' => $trashedProjectSprints,
        ])->render();
    }

    private function renderTeamTab(Project $project): string
    {
        $salesPersonIds = $project->sales_person_id ? [$project->sales_person_id] : [];
        $project->load([
            'members.details.designation',
            'members.primaryAttachment',
        ]);

        $existingMemberIds = $project->members
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $users = $this->userService
            ->getAccessibleUsers(auth()->user(), [], $salesPersonIds)
            ->reject(fn($user) => in_array((int) $user->id, $existingMemberIds, true))
            ->values();

        $projectRoles = config('project_constants.project_roles');

        return view('projects.partials.tabs.team', compact('project', 'users', 'projectRoles'))->render();
    }

    private function renderScopeTab(Project $project): string
    {
        $project->load(['scopeFiles.addedBy']);

        return view('projects.partials.tabs.scope', compact('project'))->render();
    }

    private function renderNotesTab(Project $project, Request $request): string
    {
        $projectNotes = $this->getPaginatedProjectNotes($project, (int) $request->input('notes_page', 1));

        return view('projects.partials.tabs.notes', compact('project', 'projectNotes'))->render();
    }

    private function renderHistoryTab(Project $project): string
    {
        $statusHistory = $project->statusHistories()
            ->with(['status', 'fromStatus', 'addedBy:id,name'])
            ->orderByDesc('added_at')
            ->get()
            ->map(function ($history) {
                return [
                    'from_label' => $history->fromStatus?->name ?? 'Start',
                    'from_color' => $history->fromStatus?->color ?: '#CBD5E1',
                    'to_label' => $history->status?->name ?? 'No Status',
                    'to_color' => $history->status?->color ?: '#CBD5E1',
                    'changed_at' => $this->projectServices->convertStoredTimestampToConfigTimezone($history->getRawOriginal('added_at')),
                    'changed_by' => $history->addedBy?->name ?? '--',
                    'remarks' => $history->remarks,
                ];
            })
            ->values();

        $stageHistory = $project->stageHistories()
            ->with(['stage', 'fromStage', 'addedBy:id,name'])
            ->orderByDesc('added_at')
            ->get()
            ->map(function ($history) {
                return [
                    'from_label' => $history->fromStage?->name ?? 'Start',
                    'from_color' => $history->fromStage?->color ?: '#CBD5E1',
                    'to_label' => $history->stage?->name ?? 'No Stage',
                    'to_color' => $history->stage?->color ?: '#CBD5E1',
                    'changed_at' => $this->projectServices->convertStoredTimestampToConfigTimezone($history->getRawOriginal('added_at')),
                    'changed_by' => $history->addedBy?->name ?? '--',
                    'remarks' => $history->remarks,
                ];
            })
            ->values();

        $currentStatus = [
            'label' => $project->projectStatus?->name ?? 'No Status',
            'color' => $project->projectStatus?->color ?: '#CBD5E1',
        ];
        $currentStage = [
            'label' => $project->projectStage?->name ?? 'No Stage',
            'color' => $project->projectStage?->color ?: '#CBD5E1',
        ];

        return view('projects.partials.tabs.history', compact(
            'project',
            'statusHistory',
            'stageHistory',
            'currentStatus',
            'currentStage'
        ))->render();
    }

    private function renderSettingsTab(Project $project): string
    {
        $salesPersonIds = $project->sales_person_id ? [$project->sales_person_id] : [];
        $selectedCustomerId = $project->customer_id;
        $selectedCategoryId = $project->project_category_id;
        $selectedTechnologyIds = $project->technologies()->get()->pluck('id')->map(fn($id) => (int) $id)->all();
        $selectedParentProjectId = $project->parent_project_id;

        $users = $this->userService->getAccessibleUsers(auth()->user(), [], $salesPersonIds);
        $project->load('technologies');

        $customers = Customer::forForm($selectedCustomerId)->get();
        $projectCategories = ProjectCategory::forForm($selectedCategoryId, 'sort_order')->get();
        $projectTechnologies = Technology::forForm($selectedTechnologyIds, 'sort_order')->get();
        $parentProjectOptions = Project::query()->eligibleParentOptions($project->id, $selectedParentProjectId)->get();

        $nextProjectCategorySortOrder = ((int) ProjectCategory::max('sort_order')) + 1;
        $nextProjectTechnologySortOrder = ((int) Technology::max('sort_order')) + 1;

        $priorities = config('project_constants.project_priorities');

        return view('projects.partials.tabs.settings', compact(
            'project',
            'users',
            'customers',
            'projectCategories',
            'nextProjectCategorySortOrder',
            'projectTechnologies',
            'nextProjectTechnologySortOrder',
            'priorities',
            'parentProjectOptions'
        ))->render();
    }

    private function getPaginatedProjectNotes(Project $project, int $page)
    {
        $perPage = 3;
        $total = $project->projectNotes()->count();
        $lastPage = max((int) ceil($total / $perPage), 1);
        $page = min(max($page, 1), $lastPage);

        return $project->projectNotes()
            ->with(['attachments', 'addedBy'])
            ->paginate($perPage, ['*'], 'notes_page', $page)
            ->withPath(route('projects.restore.show', $project->id))
            ->withQueryString();
    }
}

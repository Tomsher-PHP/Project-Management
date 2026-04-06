<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectFileRequest;
use App\Http\Requests\ProjectNoteRequest;
use App\Http\Requests\ProjectRequest;
use App\Http\Requests\ProjectTaskQuickStoreRequest;
use App\Http\Requests\ProjectTaskUpdateRequest;
use App\Models\Attachment;
use App\Models\AgileModule;
use App\Models\AgileModuleStatus;
use App\Models\AgileSprint;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectNote;
use App\Models\ProjectCategory;
use App\Models\ProjectSprint;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\ProjectTaskStatusHistory;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\AttachmentService;
use App\Services\ProjectServices;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Project Management';
        $this->subTitle = 'Manage your projects';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request, ProjectServices $service)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $projects = Project::accessibleBy(auth()->user())
            ->filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $projects->getCollection()->transform(function ($project) use ($service) {
            $project->project_timeline = $service->getTimelines($project)['projectTimeline'];

            return $project;
        });

        $customers = Customer::active()->get();
        $statuses = ProjectStatus::active()->orderBy('sort_order', 'asc')->get();
        $priorities = config('project_constants.project_priorities');
        $types = config('project_constants.project_flows');

        return view('projects.index', compact('projects', 'perPage', 'customers', 'statuses', 'priorities', 'types'));
    }

    public function store(ProjectRequest $request, ProjectServices $service)
    {
        $project = $service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Project created successfully.',
            'redirect_url' => route('projects.edit', $project->id),
        ], Response::HTTP_OK);
    }

    public function edit(Project $project, ProjectServices $service)
    {
        return view('projects.detail-page', array_merge([
            'project' => $project,
            'projectActivitiesCount' => $project->activities()->count(),
            'projectCommentsCount' => $project->comments()->count(),
        ], $this->getProjectHeaderData($project, $service)));
    }

    public function activityModal(Project $project): JsonResponse
    {
        $activities = $project->activities()
            ->with('causer')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.modals.activity-content', [
                'project' => $project,
                'activities' => $activities,
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function commentsModal(Project $project): JsonResponse
    {
        $comments = $project->comments()
            ->with('user.primaryAttachment')
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.modals.comments-content', [
                'project' => $project,
                'comments' => $comments,
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function tab(Request $request, Project $project, string $tab, ProjectServices $service)
    {
        $allowedTabs = ['modules', 'tasks', 'team', 'scope', 'notes', 'settings'];
        abort_unless(in_array($tab, $allowedTabs, true), Response::HTTP_NOT_FOUND);

        return response()->json([
            'status' => true,
            'tab' => $tab,
            'html' => $this->renderTab($project, $tab, $service, $request),
        ], Response::HTTP_OK);
    }

    public function taskGroup(Project $project, string $group): JsonResponse
    {
        $groupData = $this->buildProjectTaskGroups($project)->firstWhere('key', $group);

        abort_unless($groupData, Response::HTTP_NOT_FOUND);

        return response()->json([
            'status' => true,
            'group' => $groupData,
            'html' => view('projects.partials.tasks.group-body', [
                'project' => $project,
                'group' => $groupData,
                'tasks' => $this->getProjectTaskGroupTasks($project, $group),
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function storeTask(ProjectTaskQuickStoreRequest $request, Project $project): JsonResponse
    {
        $validated = $request->validated();
        $assigneeId = isset($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;
        $isLinearFlow = $project->project_flow === 'linear';
        $latestSprint = $isLinearFlow ? null : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
        $selectedSprint = $isLinearFlow || empty($validated['project_sprint_id'])
            ? null
            : ProjectSprint::query()
                ->where('project_id', $project->id)
                ->find($validated['project_sprint_id']);
        $targetSprint = $isLinearFlow ? null : ($selectedSprint ?: $latestSprint);

        $defaultStatusId = ProjectTaskStatus::query()
            ->where('flow_type', $project->project_flow)
            ->where('is_default', true)
            ->value('id');

        $project->projectTasks()->create([
            'project_module_id' => $targetSprint?->project_module_id,
            'project_sprint_id' => $targetSprint?->id,
            'title' => $validated['title'],
            'status_id' => $defaultStatusId,
            'current_assignee_id' => $assigneeId,
            'start_date' => now(config('constants.timezone'))->toDateString(),
            'estimated_time_seconds' => (int) (($validated['estimated_time_minutes'] ?? 0) * 60),
            'sort_order' => ProjectTask::nextSortOrder($project->id, $targetSprint?->id),
        ]);

        $project->refresh();

        return response()->json([
            'status' => true,
            'message' => 'Task added successfully.',
            'html' => $this->renderTasksTab(
                $project,
                $isLinearFlow ? 'all-tasks' : ($targetSprint ? 'sprint-' . $targetSprint->id : 'ungrouped')
            ),
        ], Response::HTTP_OK);
    }

    public function taskModal(Project $project, ProjectTask $task): JsonResponse
    {
        abort_unless((int) $task->project_id === (int) $project->id, Response::HTTP_NOT_FOUND);
        abort_unless(auth()->user()->can('view', $task), Response::HTTP_FORBIDDEN);

        $task->load([
            'projectModule:id,name',
            'projectSprint:id,name,project_module_id',
            'parentTask:id,title',
            'currentAssignee.primaryAttachment',
            'status:id,name,color',
            'tags:id,name,color',
            'addedBy:id,name',
            'updatedBy:id,name',
        ]);

        return response()->json([
            'status' => true,
            'html' => view('projects.partials.tasks.modals.detail-content', array_merge([
                'project' => $project,
                'task' => $task,
            ], $this->getTaskModalData($project, $task)))->render(),
        ], Response::HTTP_OK);
    }

    public function updateTask(ProjectTaskUpdateRequest $request, Project $project, ProjectTask $task): JsonResponse
    {
        abort_unless((int) $task->project_id === (int) $project->id, Response::HTTP_NOT_FOUND);
        abort_unless(auth()->user()->can('update', $task), Response::HTTP_FORBIDDEN);

        $validated = $request->validated();
        $isLinearFlow = $project->project_flow === 'linear';
        $hasSprintField = array_key_exists('project_sprint_id', $validated);
        $selectedSprint = $isLinearFlow || ! $hasSprintField || empty($validated['project_sprint_id'])
            ? null
            : ProjectSprint::query()
                ->where('project_id', $project->id)
                ->find($validated['project_sprint_id']);
        $resolvedModuleId = $hasSprintField
            ? ($selectedSprint?->project_module_id ?? $task->project_module_id)
            : $task->project_module_id;
        $resolvedSprintId = $hasSprintField
            ? ($isLinearFlow ? null : $selectedSprint?->id)
            : $task->project_sprint_id;
        $newStatusId = ! empty($validated['status_id']) ? (int) $validated['status_id'] : null;
        $newAssigneeId = ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;
        $previousStatusId = (int) ($task->status_id ?? 0);
        $previousAssigneeId = (int) ($task->current_assignee_id ?? 0);

        DB::transaction(function () use (
            $validated,
            $task,
            $resolvedModuleId,
            $resolvedSprintId,
            $newStatusId,
            $newAssigneeId,
            $previousStatusId,
            $previousAssigneeId
        ) {
            $task->update([
                'project_module_id' => $resolvedModuleId,
                'project_sprint_id' => $resolvedSprintId,
                'parent_task_id' => ! empty($validated['parent_task_id']) ? (int) $validated['parent_task_id'] : null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status_id' => $newStatusId,
                'task_type' => $validated['task_type'],
                'task_mode' => $validated['task_mode'],
                'priority' => $validated['priority'],
                'current_assignee_id' => $newAssigneeId,
                'start_date' => $validated['start_date'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'completed_at' => $validated['completed_at'] ?? $task->completed_at,
                'estimated_time_seconds' => (int) (($validated['estimated_time_minutes'] ?? 0) * 60),
                'is_billable' => (bool) ($validated['is_billable'] ?? false),
                'sort_order' => ! empty($validated['sort_order']) ? (int) $validated['sort_order'] : $task->sort_order,
            ]);

            if (array_key_exists('tag_ids', $validated)) {
                $task->tags()->sync($this->resolveTaskTagIds($validated['tag_ids'] ?? []));
            }

            if ($newStatusId && $newStatusId !== $previousStatusId) {
                ProjectTaskStatusHistory::create([
                    'project_task_id' => $task->id,
                    'status_id' => $newStatusId,
                ]);
            }

            if ($newAssigneeId !== ($previousAssigneeId ?: null)) {
                $this->syncTaskAssignmentState($task, $newAssigneeId);
            }
        });

        $task->refresh();

        return response()->json([
            'status' => true,
            'message' => 'Task updated successfully.',
            'html' => $this->renderTasksTab($project, $this->resolveTaskGroupKey($project, $task)),
        ], Response::HTTP_OK);
    }

    public function update(ProjectRequest $request, Project $project, ProjectServices $service)
    {
        $project = $service->update($project, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'project' => $project,
            'project_header' => $this->renderProjectHeader($project, $service),
        ], Response::HTTP_OK);
    }

    public function updateProjectStatus(Request $request, Project $project, ProjectServices $service)
    {
        $validated = $request->validate([
            'status_id' => 'required|exists:project_statuses,id',
        ]);

        $project = $service->updateStatus($project, (int) $validated['status_id']);

        return response()->json([
            'success' => true,
            'message' => 'Project status updated successfully.',
            'project_header' => $this->renderProjectHeader($project, $service),
        ], Response::HTTP_OK);
    }

    public function updateProjectStage(Request $request, Project $project, ProjectServices $service)
    {
        $request->merge([
            'project_stage_id' => $request->filled('project_stage_id') ? $request->input('project_stage_id') : null,
        ]);

        $validated = $request->validate([
            'project_stage_id' => 'nullable|exists:project_stages,id',
        ]);

        $project = $service->updateStage(
            $project,
            isset($validated['project_stage_id']) ? (int) $validated['project_stage_id'] : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Project stage updated successfully.',
            'project_header' => $this->renderProjectHeader($project, $service),
        ], Response::HTTP_OK);
    }

    public function storeNote(ProjectNoteRequest $request, Project $project, ProjectServices $service)
    {
        $service->createNote($project, $request->validated());
        $projectNotes = $this->getPaginatedProjectNotes($project, 1);

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully.',
            'html' => view('projects.partials.project-notes-list', [
                'projectNotes' => $projectNotes,
                'canRemove' => auth()->user()->can('project.remove_notes_files'),
            ])->render(),
            'current_page' => $projectNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function deleteNote(Request $request, Project $project, ProjectNote $note, AttachmentService $attachmentService)
    {
        abort_unless($note->project_id === $project->id, Response::HTTP_NOT_FOUND);

        $attachmentService->delete($note->attachments);
        $note->delete();
        $projectNotes = $this->getPaginatedProjectNotes($project, (int) $request->input('notes_page', 1));

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully.',
            'html' => view('projects.partials.project-notes-list', [
                'projectNotes' => $projectNotes,
                'canRemove' => auth()->user()->can('project.remove_notes_files'),
            ])->render(),
            'current_page' => $projectNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function deleteNoteAttachment(Request $request, Project $project, ProjectNote $note, Attachment $attachment, AttachmentService $attachmentService)
    {
        abort_unless($note->project_id === $project->id, Response::HTTP_NOT_FOUND);
        abort_unless(
            $attachment->link_type === ProjectNote::class && (int) $attachment->link_id === (int) $note->id,
            Response::HTTP_NOT_FOUND
        );

        $attachmentService->delete(collect([$attachment]));
        $projectNotes = $this->getPaginatedProjectNotes($project, (int) $request->input('notes_page', 1));

        return response()->json([
            'success' => true,
            'message' => 'File removed successfully.',
            'html' => view('projects.partials.project-notes-list', [
                'projectNotes' => $projectNotes,
                'canRemove' => auth()->user()->can('project.remove_notes_files'),
            ])->render(),
            'current_page' => $projectNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function uploadScopeFile(ProjectFileRequest $request, Project $project, ProjectServices $service)
    {
        $attachments = $service->uploadFile($project, $request->validated(), 'scope_files');

        $html = [];
        foreach ($attachments as $file) {
            $file->load('addedBy');
            $html[] = view('projects.partials.file-item', ['file' => $file])->render();
        }

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully',
            'html' => $html
        ], Response::HTTP_OK);
    }

    public function deleteScopeFile(Project $project, $fileId, AttachmentService $attachmentService)
    {
        $attachment = $project->attachments()->where('id', $fileId)->get();
        $attachmentService->delete($attachment);

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully.',
        ], Response::HTTP_OK);
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $project = Project::findOrFail($request->id);
        $project->is_active = !$project->is_active;
        $project->save();

        return response()->json([
            'success' => true,
            'is_active' => $project->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
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
            ->withPath(route('projects.edit', $project))
            ->withQueryString();
    }

    private function renderTab(Project $project, string $tab, ProjectServices $service, Request $request): string
    {
        return match ($tab) {
            'modules' => $this->renderModulesTab($project),
            'tasks' => $this->renderTasksTab($project),
            'team' => $this->renderTeamTab($project),
            'scope' => $this->renderScopeTab($project),
            'notes' => $this->renderNotesTab($project, $request),
            'settings' => $this->renderSettingsTab($project),
            default => abort(Response::HTTP_NOT_FOUND),
        };
    }

    private function renderModulesTab(Project $project): string
    {
        $projectModules = $project->projectModules()
            ->with([
                'addedBy',
                'updatedBy',
                'status',
                'owner',
            ])
            ->withCount('projectSprints')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $agileModules = AgileModule::active()->orderBy('sort_order', 'asc')->get();
        $agileSprints = AgileSprint::active()->orderBy('sort_order', 'asc')->get();
        $agileModuleStatuses = AgileModuleStatus::active()->orderBy('sort_order', 'asc')->get();
        $assignableUsers = app(UserService::class)->getAccessibleUsers(auth()->user());
        $trashedProjectModules = ProjectModule::onlyTrashed()
            ->where('project_id', $project->id)
            ->orderByDesc('deleted_at')
            ->get();
        $trashedProjectSprintsByModule = ProjectSprint::onlyTrashed()
            ->where('project_id', $project->id)
            ->orderByDesc('deleted_at')
            ->get()
            ->groupBy('project_module_id');

        return view('projects.partials.tabs.modules', compact(
            'project',
            'projectModules',
            'agileModules',
            'agileSprints',
            'agileModuleStatuses',
            'assignableUsers',
            'trashedProjectModules',
            'trashedProjectSprintsByModule'
        ))->render();
    }

    private function renderTeamTab(Project $project): string
    {
        $salesPersonIds = $project->sales_person_id ? [$project->sales_person_id] : [];
        $users = app(UserService::class)->getAccessibleUsers(auth()->user(), [], $salesPersonIds);
        $projectRoles = config('project_constants.project_roles');
        $project->load('members');

        return view('projects.partials.tabs.team', compact('project', 'users', 'projectRoles'))->render();
    }

    private function renderTasksTab(Project $project, ?string $preferredGroupKey = null): string
    {
        $taskGroups = $this->buildProjectTaskGroups($project);
        $isLinearFlow = $project->project_flow === 'linear';
        $latestSprint = $isLinearFlow ? null : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
        $projectSprints = $isLinearFlow ? collect() : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->with(['projectModule:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $initialGroupKey = $preferredGroupKey && $taskGroups->contains(fn ($group) => $group['key'] === $preferredGroupKey)
            ? $preferredGroupKey
            : ($taskGroups->first()['key'] ?? null);
        $initialTasks = $initialGroupKey
            ? $this->getProjectTaskGroupTasks($project, $initialGroupKey)
            : collect();
        $assignableUsers = $project->activeMembers()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);

        return view('projects.partials.tabs.tasks', [
            'project' => $project,
            'taskGroups' => $taskGroups,
            'initialGroupKey' => $initialGroupKey,
            'initialTasks' => $initialTasks,
            'totalTaskCount' => (int) $taskGroups->sum('task_count'),
            'sprintCount' => (int) $taskGroups->where('is_unscheduled', false)->count(),
            'isLinearFlow' => $isLinearFlow,
            'assignableUsers' => $assignableUsers,
            'projectSprints' => $projectSprints,
            'defaultSprintId' => $latestSprint?->id,
        ])->render();
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

    private function renderSettingsTab(Project $project): string
    {
        $salesPersonIds = $project->sales_person_id ? [$project->sales_person_id] : [];
        $users = app(UserService::class)->getAccessibleUsers(auth()->user(), [], $salesPersonIds);
        $project->load('technologies');

        $customers = Customer::active()->get();
        $statuses = ProjectStatus::active()->orderBy('sort_order', 'asc')->get();
        $projectCategories = ProjectCategory::active()->orderBy('sort_order', 'asc')->get();
        $projectTechnologies = Technology::active()->orderBy('sort_order', 'asc')->get();
        $projectStages = ProjectStage::active()->orderBy('sort_order', 'asc')->get();
        $priorities = config('project_constants.project_priorities');

        return view('projects.partials.tabs.settings', compact(
            'project',
            'users',
            'customers',
            'statuses',
            'projectCategories',
            'projectTechnologies',
            'projectStages',
            'priorities'
        ))->render();
    }

    private function buildProjectTaskGroups(Project $project): Collection
    {
        $authUser = auth()->user();

        if ($project->project_flow === 'linear') {
            $allTasks = ProjectTask::query()
                ->where('project_id', $project->id)
                ->accessibleBy($authUser);
            $taskCount = (clone $allTasks)->count();
            $estimatedSeconds = (int) (clone $allTasks)->sum('estimated_time_seconds');
            $derivedSeconds = (int) (clone $allTasks)->sum('derived_time_seconds');
            $actualSeconds = (int) (clone $allTasks)->sum('actual_time_seconds');

            return collect([[
                'key' => 'all-tasks',
                'sprint_id' => null,
                'name' => 'All Tasks',
                'subtitle' => null,
                'accent_color' => '#3B82F6',
                'task_count' => $taskCount,
                'estimated_seconds' => $estimatedSeconds,
                'estimated_label' => $this->formatSecondsShort($estimatedSeconds),
                'derived_seconds' => $derivedSeconds,
                'derived_label' => $this->formatSecondsShort($derivedSeconds),
                'actual_seconds' => $actualSeconds,
                'actual_label' => $this->formatSecondsShort($actualSeconds),
                'date_label' => null,
                'created_label' => null,
                'is_latest' => true,
                'is_unscheduled' => false,
                'is_linear_group' => true,
            ]]);
        }

        $taskGroups = ProjectSprint::query()
            ->where('project_id', $project->id)
            ->with(['projectModule:id,name'])
            ->withCount([
                'projectTasks' => fn ($query) => $query->accessibleBy($authUser),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (ProjectSprint $projectSprint, int $index) {
                $sprintEstimatedSeconds = (int) ($projectSprint->estimated_time_seconds ?? 0);
                $sprintDerivedSeconds = (int) ($projectSprint->derived_time_seconds ?? 0);
                $sprintActualSeconds = (int) ($projectSprint->actual_time_seconds ?? 0);

                return [
                    'key' => 'sprint-' . $projectSprint->id,
                    'sprint_id' => $projectSprint->id,
                    'name' => $projectSprint->name,
                    'subtitle' => $projectSprint->projectModule?->name,
                    'accent_color' => $projectSprint->color ?: '#22C55E',
                    'task_count' => (int) $projectSprint->project_tasks_count,
                    'estimated_seconds' => $sprintEstimatedSeconds,
                    'estimated_label' => $this->formatSecondsShort($sprintEstimatedSeconds),
                    'derived_seconds' => $sprintDerivedSeconds,
                    'derived_label' => $this->formatSecondsShort($sprintDerivedSeconds),
                    'actual_seconds' => $sprintActualSeconds,
                    'actual_label' => $this->formatSecondsShort($sprintActualSeconds),
                    'date_label' => $this->formatDateRange($projectSprint->start_date, $projectSprint->end_date),
                    'created_label' => $projectSprint->created_at
                        ? AppServiceProvider::formatAppDate($projectSprint->created_at)
                        : null,
                    'is_latest' => $index === 0,
                    'is_unscheduled' => false,
                    'is_linear_group' => false,
                ];
            });

        $ungroupedTasks = ProjectTask::query()
            ->where('project_id', $project->id)
            ->whereNull('project_sprint_id')
            ->accessibleBy($authUser);

        $ungroupedCount = (clone $ungroupedTasks)->count();

        if ($ungroupedCount > 0) {
            $ungroupedEstimatedSeconds = (int) (clone $ungroupedTasks)->sum('estimated_time_seconds');
            $ungroupedDerivedSeconds = (int) (clone $ungroupedTasks)->sum('derived_time_seconds');
            $ungroupedActualSeconds = (int) (clone $ungroupedTasks)->sum('actual_time_seconds');

            $taskGroups->push([
                'key' => 'ungrouped',
                'sprint_id' => null,
                'name' => 'Unscheduled Tasks',
                'subtitle' => 'Tasks without a sprint',
                'accent_color' => '#94A3B8',
                'task_count' => $ungroupedCount,
                'estimated_seconds' => $ungroupedEstimatedSeconds,
                'estimated_label' => $this->formatSecondsShort($ungroupedEstimatedSeconds),
                'derived_seconds' => $ungroupedDerivedSeconds,
                'derived_label' => $this->formatSecondsShort($ungroupedDerivedSeconds),
                'actual_seconds' => $ungroupedActualSeconds,
                'actual_label' => $this->formatSecondsShort($ungroupedActualSeconds),
                'date_label' => 'No sprint dates',
                'created_label' => null,
                'is_latest' => $taskGroups->isEmpty(),
                'is_unscheduled' => true,
                'is_linear_group' => false,
            ]);
        }

        return $taskGroups->values();
    }

    private function resolveTaskTagIds(array $submittedTags): array
    {
        return collect($submittedTags)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => filled($value))
            ->map(function ($value) {
                if (is_numeric($value)) {
                    $existingId = Tag::query()->whereKey((int) $value)->value('id');

                    if ($existingId) {
                        return (int) $existingId;
                    }
                }

                return $this->firstOrCreateTaskTag((string) $value)->id;
            })
            ->unique()
            ->values()
            ->all();
    }

    private function firstOrCreateTaskTag(string $name): Tag
    {
        $cleanName = trim($name);
        $baseSlug = Str::slug($cleanName);
        $slug = $baseSlug !== '' ? $baseSlug : Str::lower(Str::random(8));

        $existingTag = Tag::withTrashed()
            ->whereRaw('LOWER(name) = ?', [Str::lower($cleanName)])
            ->orWhere('slug', $slug)
            ->first();

        if ($existingTag) {
            if ($existingTag->trashed()) {
                $existingTag->restore();
            }

            if (! $existingTag->is_active) {
                $existingTag->is_active = true;
                $existingTag->save();
            }

            return $existingTag;
        }

        $candidateSlug = $slug;
        $suffix = 2;

        while (Tag::withTrashed()->where('slug', $candidateSlug)->exists()) {
            $candidateSlug = $slug . '-' . $suffix;
            $suffix++;
        }

        return Tag::create([
            'name' => $cleanName,
            'slug' => $candidateSlug,
            'type' => 'general',
            'is_active' => true,
            'is_system' => false,
        ]);
    }

    private function getProjectTaskGroupTasks(Project $project, string $groupKey): Collection
    {
        $query = ProjectTask::query()
            ->where('project_id', $project->id)
            ->accessibleBy(auth()->user())
            ->with([
                'currentAssignee.primaryAttachment',
                'status',
                'tags',
                'parentTask:id,title',
            ])
            ->withCount('childTasks')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($groupKey === 'ungrouped') {
            $query->whereNull('project_sprint_id');
        } elseif ($groupKey === 'all-tasks') {
            // Linear-flow projects display a flat task list without sprint grouping.
        } else {
            abort_unless(str_starts_with($groupKey, 'sprint-'), Response::HTTP_NOT_FOUND);

            $projectSprintId = (int) str_replace('sprint-', '', $groupKey);

            abort_unless(
                $project->projectSprints()->whereKey($projectSprintId)->exists(),
                Response::HTTP_NOT_FOUND
            );

            $query->where('project_sprint_id', $projectSprintId);
        }

        return $query->get();
    }

    private function getTaskModalData(Project $project, ?ProjectTask $task = null): array
    {
        $taskTypeOptions = collect(config('project_constants.task_type', []))
            ->map(fn ($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
            ->values();
        $taskModeOptions = collect(config('project_constants.task_mode', []))
            ->map(fn ($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
            ->values();
        $taskPriorityOptions = collect(config('project_constants.task_priorities', []))
            ->map(fn ($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
            ->values();

        return [
            'canEditTask' => auth()->user()->can('update', $task),
            'isLinearFlow' => $project->project_flow === 'linear',
            'taskStatuses' => ProjectTaskStatus::query()
                ->active()
                ->forFlow($project->project_flow)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'color']),
            'projectModules' => ProjectModule::query()
                ->where('project_id', $project->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'projectSprints' => ProjectSprint::query()
                ->where('project_id', $project->id)
                ->with(['projectModule:id,name'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id', 'project_module_id', 'name']),
            'assignableUsers' => $project->activeMembers()
                ->orderBy('users.name')
                ->get(['users.id', 'users.name']),
            'parentTaskOptions' => ProjectTask::query()
                ->where('project_id', $project->id)
                ->accessibleBy(auth()->user())
                ->when($task, fn ($query) => $query->whereKeyNot($task->id))
                ->orderBy('title')
                ->get(['id', 'title']),
            'tagOptions' => Tag::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'color']),
            'taskTypeOptions' => $taskTypeOptions,
            'taskModeOptions' => $taskModeOptions,
            'taskPriorityOptions' => $taskPriorityOptions,
        ];
    }

    private function syncTaskAssignmentState(ProjectTask $task, ?int $newAssigneeId): void
    {
        $currentLog = $task->currentAssignmentLog()->first();
        $now = now(config('constants.timezone'));

        if ($currentLog) {
            $currentLog->update([
                'assigned_to' => $now,
                'is_current' => false,
            ]);
        }

        if ($newAssigneeId) {
            $task->assignmentLogs()->create([
                'user_id' => $newAssigneeId,
                'assigned_from' => $now,
                'is_current' => true,
            ]);
        }
    }

    private function resolveTaskGroupKey(Project $project, ProjectTask $task): string
    {
        if ($project->project_flow === 'linear') {
            return 'all-tasks';
        }

        if ($task->project_sprint_id) {
            return 'sprint-' . $task->project_sprint_id;
        }

        return 'ungrouped';
    }

    private function formatDateRange($startDate, $endDate): string
    {
        if ($startDate && $endDate) {
            return AppServiceProvider::formatAppDate($startDate)
                . ' - ' . AppServiceProvider::formatAppDate($endDate);
        }

        if ($startDate) {
            return 'Starts ' . AppServiceProvider::formatAppDate($startDate);
        }

        if ($endDate) {
            return 'Ends ' . AppServiceProvider::formatAppDate($endDate);
        }

        return 'No sprint dates';
    }

    private function formatSecondsShort(int $seconds): string
    {
        $totalSeconds = max(0, $seconds);

        if ($totalSeconds === 0) {
            return '0h';
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        if ($hours === 0) {
            return $minutes . 'm';
        }

        if ($minutes === 0) {
            return $hours . 'h';
        }

        return $hours . 'h ' . $minutes . 'm';
    }

    private function getProjectHeaderData(Project $project, ProjectServices $service): array
    {
        $project->loadMissing(['customer', 'projectStatus', 'projectStage', 'addedBy']);
        $timelines = $service->getTimelines($project);

        return [
            'priority' => config('project_constants.project_priorities')[$project->priority] ?? null,
            'projectTimeline' => $timelines['projectTimeline'],
            'customerTimeline' => $timelines['customerTimeline'],
            'projectStatuses' => ProjectStatus::active()->orderBy('sort_order', 'asc')->get(),
            'projectStages' => ProjectStage::active()->orderBy('sort_order', 'asc')->get(),
        ];
    }

    private function renderProjectHeader(Project $project, ProjectServices $service): string
    {
        return view('projects.partials.header', array_merge([
            'project' => $project,
        ], $this->getProjectHeaderData($project, $service)))->render();
    }
}

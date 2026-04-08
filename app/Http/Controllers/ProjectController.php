<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectFileRequest;
use App\Http\Requests\ProjectCommentRequest;
use App\Http\Requests\ProjectNoteRequest;
use App\Http\Requests\ProjectRequest;
use App\Http\Requests\TaskQuickStoreRequest;
use App\Http\Requests\TaskProjectUpdateRequest;
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
use App\Models\Task;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskStatusHistory;
use App\Models\TaskType;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    private const TASK_GROUPS_PER_PAGE = 5;
    private const TASKS_PER_GROUP_PAGE = 5;

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
        $comments = $this->getRecentProjectComments($project);
        $totalComments = $project->comments()->count();

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.modals.comments-content', [
                'project' => $project,
                'comments' => $comments,
                'totalComments' => $totalComments,
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function storeComment(ProjectCommentRequest $request, Project $project): JsonResponse
    {
        $project->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->validated()['comment'],
        ]);

        $comments = $this->getRecentProjectComments($project);
        $totalComments = $project->comments()->count();

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'count' => $totalComments,
            'html' => view('projects.partials.modals.comments-content', [
                'project' => $project,
                'comments' => $comments,
                'totalComments' => $totalComments,
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function tab(Request $request, Project $project, string $tab, ProjectServices $service)
    {
        $allowedTabs = ['modules', 'tasks', 'team', 'scope', 'notes', 'history', 'settings'];
        abort_unless(in_array($tab, $allowedTabs, true), Response::HTTP_NOT_FOUND);

        return response()->json([
            'status' => true,
            'tab' => $tab,
            'html' => $this->renderTab($project, $tab, $service, $request),
        ], Response::HTTP_OK);
    }

    public function taskGroup(Project $project, string $group): JsonResponse
    {
        $groupData = $this->findTaskGroup($project, $group);

        abort_unless($groupData, Response::HTTP_NOT_FOUND);
        $taskPage = $this->getTaskGroupTaskPage(
            $project,
            $group,
            max((int) request()->integer('page', 1), 1),
            self::TASKS_PER_GROUP_PAGE
        );

        return response()->json([
            'status' => true,
            'group' => $groupData,
            'html' => view('projects.partials.tasks.group-body', [
                'project' => $project,
                'group' => $groupData,
                'tasks' => $taskPage['tasks'],
                'pagination' => $taskPage['pagination'],
            ])->render(),
            'items_html' => view('projects.partials.tasks.task-rows', [
                'project' => $project,
                'group' => $groupData,
                'tasks' => $taskPage['tasks'],
                'showEmptyState' => false,
            ])->render(),
            'pagination' => $taskPage['pagination'],
        ], Response::HTTP_OK);
    }

    public function taskGroupsPage(Request $request, Project $project): JsonResponse
    {
        $page = max((int) $request->integer('page', 1), 1);
        $pageData = $this->getTaskGroupPage($project, $page, self::TASK_GROUPS_PER_PAGE);

        return response()->json([
            'status' => true,
            'html' => view('projects.partials.tasks.group-cards', [
                'project' => $project,
                'taskGroups' => $pageData['taskGroups'],
                'initialGroupKey' => null,
                'initialTasks' => collect(),
            ])->render(),
            'pagination' => $pageData['pagination'],
        ], Response::HTTP_OK);
    }

    public function taskParentOptions(Request $request, Project $project): JsonResponse
    {
        $sprintId = $request->filled('project_sprint_id') ? (int) $request->input('project_sprint_id') : null;
        $query = Task::query()
            ->where('project_id', $project->id)
            ->orderBy('title')
            ->orderBy('id');

        if ($project->project_flow === 'linear') {
            $query->whereNull('project_sprint_id');
        } elseif ($sprintId) {
            abort_unless(
                ProjectSprint::query()
                    ->where('project_id', $project->id)
                    ->whereKey($sprintId)
                    ->exists(),
                Response::HTTP_NOT_FOUND
            );

            $query->where('project_sprint_id', $sprintId);
        } else {
            return response()->json([
                'status' => true,
                'options' => [],
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => true,
            'options' => $query->get(['id', 'title', 'code'])->map(function (Task $task) {
                return [
                    'value' => (string) $task->id,
                    'text' => $task->title,
                    'subtype' => $task->code ?: 'Parent task',
                ];
            })->values(),
        ], Response::HTTP_OK);
    }

    public function storeTask(TaskQuickStoreRequest $request, Project $project): JsonResponse
    {
        $validated = $request->validated();
        $assigneeId = isset($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;
        $isLinearFlow = $project->project_flow === 'linear';
        $latestSprint = $isLinearFlow ? null : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
        $selectedSprint = $isLinearFlow || empty($validated['project_sprint_id'])
            ? null
            : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->find($validated['project_sprint_id']);
        $targetSprint = $isLinearFlow ? null : ($selectedSprint ?: $latestSprint);

        $defaultStatusId = TaskStatus::query()
            ->where('flow_type', $project->project_flow)
            ->where('is_default', true)
            ->value('id');
        $defaultTaskType = TaskType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('code') ?: 'feature';
        $defaultTaskMode = TaskMode::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('code') ?: 'new';
        $defaultTaskPriority = array_key_first(config('project_constants.task_priorities', [])) ?: 'medium';

        $task = $project->tasks()->create([
            'project_module_id' => $targetSprint?->project_module_id,
            'project_sprint_id' => $targetSprint?->id,
            'parent_task_id' => ! empty($validated['parent_task_id']) ? (int) $validated['parent_task_id'] : null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status_id' => ! empty($validated['status_id']) ? (int) $validated['status_id'] : $defaultStatusId,
            'task_type_id' => $validated['task_type_id'] ?? $defaultTaskType,
            'task_mode_id' => $validated['task_mode_id'] ?? $defaultTaskMode,
            'priority' => $validated['priority'] ?? $defaultTaskPriority,
            'current_assignee_id' => $assigneeId,
            'start_date' => $validated['start_date'] ?? now(config('constants.timezone'))->toDateString(),
            'due_date' => $validated['due_date'] ?? null,
            'estimated_time_seconds' => (int) (($validated['estimated_time_minutes'] ?? 0) * 60),
            'is_billable' => (bool) ($validated['is_billable'] ?? false),
            'sort_order' => Task::nextSortOrder($project->id, $targetSprint?->id),
        ]);

        if (array_key_exists('tag_ids', $validated)) {
            $task->tags()->sync($this->resolveTaskTagIds($validated['tag_ids'] ?? []));
        }

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

    public function taskModal(Project $project, Task $task): JsonResponse
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

    public function updateTask(TaskProjectUpdateRequest $request, Project $project, Task $task): JsonResponse
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
                'task_type_id' => $validated['task_type_id'] ?? null,
                'task_mode_id' => $validated['task_mode_id'] ?? null,
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
                TaskStatusHistory::create([
                    'task_id' => $task->id,
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
        $latestStatusChangeDate = $this->getLatestProjectStatusChangeDate($project);
        $validator = Validator::make($request->all(), [
            'status_id' => 'required|exists:project_statuses,id',
            'change_date' => 'required|date',
            'remarks' => 'nullable|string|max:150',
        ]);
        $this->applyProjectChangeDateValidation($validator, $request->input('change_date'), $latestStatusChangeDate);
        $validated = $validator->validate();

        $project = $service->updateStatus(
            $project,
            (int) $validated['status_id'],
            $validated['change_date'],
            $validated['remarks'] ?? null
        );

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

        $latestStageChangeDate = $this->getLatestProjectStageChangeDate($project);
        $validator = Validator::make($request->all(), [
            'project_stage_id' => 'nullable|exists:project_stages,id',
            'change_date' => 'required|date',
            'remarks' => 'nullable|string|max:150',
        ]);
        $this->applyProjectChangeDateValidation($validator, $request->input('change_date'), $latestStageChangeDate);
        $validated = $validator->validate();

        $project = $service->updateStage(
            $project,
            isset($validated['project_stage_id']) ? (int) $validated['project_stage_id'] : null,
            $validated['change_date'],
            $validated['remarks'] ?? null
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
            'history' => $this->renderHistoryTab($project),
            'settings' => $this->renderSettingsTab($project),
            default => abort(Response::HTTP_NOT_FOUND),
        };
    }

    private function getRecentProjectComments(Project $project, int $limit = 10): Collection
    {
        return $project->comments()
            ->with('user.primaryAttachment')
            ->latest()
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();
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
        $assignableUsers = $project->activeMembers()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);
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
        $project->load('members');

        $existingMemberIds = $project->members
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $users = app(UserService::class)
            ->getAccessibleUsers(auth()->user(), [], $salesPersonIds)
            ->reject(fn($user) => in_array((int) $user->id, $existingMemberIds, true))
            ->values();

        $projectRoles = config('project_constants.project_roles');

        return view('projects.partials.tabs.team', compact('project', 'users', 'projectRoles'))->render();
    }

    private function renderTasksTab(Project $project, ?string $preferredGroupKey = null): string
    {
        $isLinearFlow = $project->project_flow === 'linear';
        $latestSprint = $isLinearFlow ? null : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
        $taskGroupViewData = $this->getInitialTaskGroupViewData($project, $preferredGroupKey);
        $taskGroups = $taskGroupViewData['taskGroups'];
        $projectSprints = $isLinearFlow ? collect() : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->with(['projectModule:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $initialGroupKey = $taskGroupViewData['initialGroupKey'];
        $initialTaskPage = $initialGroupKey
            ? $this->getTaskGroupTaskPage($project, $initialGroupKey, 1, self::TASKS_PER_GROUP_PAGE)
            : ['tasks' => collect(), 'pagination' => ['page' => 1, 'next_page' => null, 'has_more_pages' => false]];
        $assignableUsers = $project->activeMembers()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);
        $taskTypeOptions = TaskType::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color']);
        $taskModeOptions = TaskMode::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color']);
        $taskPriorityOptions = collect(config('project_constants.task_priorities', []))
            ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
            ->values();
        $taskStatuses = TaskStatus::query()
            ->active()
            ->forFlow($project->project_flow)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'color']);
        $tagOptions = Tag::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return view('projects.partials.tabs.tasks', [
            'project' => $project,
            'taskGroups' => $taskGroups,
            'initialGroupKey' => $initialGroupKey,
            'initialTasks' => $initialTaskPage['tasks'],
            'initialTasksPagination' => $initialTaskPage['pagination'],
            'totalTaskCount' => $taskGroupViewData['totalTaskCount'],
            'sprintCount' => $taskGroupViewData['sprintCount'],
            'isLinearFlow' => $isLinearFlow,
            'assignableUsers' => $assignableUsers,
            'projectSprints' => $projectSprints,
            'defaultSprintId' => $latestSprint?->id,
            'taskGroupsPagination' => $taskGroupViewData['pagination'],
            'taskStatuses' => $taskStatuses,
            'taskTypeOptions' => $taskTypeOptions,
            'taskModeOptions' => $taskModeOptions,
            'taskPriorityOptions' => $taskPriorityOptions,
            'tagOptions' => $tagOptions,
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

    private function renderHistoryTab(Project $project): string
    {
        $statusHistory = $project->statusHistories()
            ->with(['status', 'fromStatus', 'addedBy:id,name'])
            ->reorder('added_at')
            ->orderBy('id')
            ->get()
            ->map(function ($history) {
                return [
                    'from_label' => $history->fromStatus?->name ?? 'Start',
                    'from_color' => $history->fromStatus?->color ?: '#CBD5E1',
                    'to_label' => $history->status?->name ?? 'No Status',
                    'to_color' => $history->status?->color ?: '#CBD5E1',
                    'changed_at' => $this->convertStoredTimestampToConfigTimezone($history->getRawOriginal('added_at')),
                    'changed_by' => $history->addedBy?->name ?? '--',
                    'remarks' => $history->remarks,
                ];
            })
            ->values();

        $stageHistory = $project->stageHistories()
            ->with(['stage', 'fromStage', 'addedBy:id,name'])
            ->reorder('added_at')
            ->orderBy('id')
            ->get()
            ->map(function ($history) {
                return [
                    'from_label' => $history->fromStage?->name ?? 'Start',
                    'from_color' => $history->fromStage?->color ?: '#CBD5E1',
                    'to_label' => $history->stage?->name ?? 'No Stage',
                    'to_color' => $history->stage?->color ?: '#CBD5E1',
                    'changed_at' => $this->convertStoredTimestampToConfigTimezone($history->getRawOriginal('added_at')),
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

    private function buildTaskGroups(Project $project): Collection
    {
        if ($project->project_flow === 'linear') {
            return collect([$this->buildLinearTaskGroup($project)]);
        }

        $pageData = $this->getTaskGroupPage($project, 1, max($this->getSprintCount($project), 1));

        return $pageData['taskGroups'];
    }

    private function getInitialTaskGroupViewData(Project $project, ?string $preferredGroupKey = null): array
    {
        if ($project->project_flow === 'linear') {
            $taskGroup = $this->buildLinearTaskGroup($project);

            return [
                'taskGroups' => collect([$taskGroup]),
                'initialGroupKey' => 'all-tasks',
                'totalTaskCount' => $taskGroup['task_count'],
                'sprintCount' => 0,
                'pagination' => [
                    'page' => 1,
                    'next_page' => null,
                    'has_more_pages' => false,
                ],
            ];
        }

        $perPage = self::TASK_GROUPS_PER_PAGE;
        $loadedPages = $this->resolveTaskGroupLoadedPages($project, $preferredGroupKey, $perPage);
        $sprintCount = $this->getSprintCount($project);
        $loadedSprintLimit = max($loadedPages * $perPage, $perPage);
        $taskGroups = ProjectSprint::query()
            ->where('project_id', $project->id)
            ->with(['projectModule:id,name'])
            ->withCount([
                'tasks' => fn($query) => $query->accessibleBy(auth()->user()),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit($loadedSprintLimit)
            ->get()
            ->values()
            ->map(fn(ProjectSprint $projectSprint, int $index) => $this->mapProjectSprintToTaskGroup(
                $projectSprint,
                $index
            ));
        $ungroupedTasks = Task::query()
            ->where('project_id', $project->id)
            ->whereNull('project_sprint_id')
            ->accessibleBy(auth()->user());
        $ungroupedCount = (clone $ungroupedTasks)->count();

        if ($ungroupedCount > 0 && $loadedSprintLimit >= $sprintCount) {
            $taskGroups->push($this->buildUngroupedTaskGroup($ungroupedTasks, $taskGroups->isEmpty()));
        }

        $initialGroupKey = $preferredGroupKey && $taskGroups->contains(fn($group) => $group['key'] === $preferredGroupKey)
            ? $preferredGroupKey
            : ($taskGroups->first()['key'] ?? null);
        $totalTaskCount = (int) Task::query()
            ->where('project_id', $project->id)
            ->accessibleBy(auth()->user())
            ->count();
        $hasMorePages = $loadedSprintLimit < $sprintCount;

        return [
            'taskGroups' => $taskGroups,
            'initialGroupKey' => $initialGroupKey,
            'totalTaskCount' => $totalTaskCount,
            'sprintCount' => $sprintCount,
            'pagination' => [
                'page' => $loadedPages,
                'next_page' => $hasMorePages ? $loadedPages + 1 : null,
                'has_more_pages' => $hasMorePages,
            ],
        ];
    }

    private function getTaskGroupPage(Project $project, int $page, int $perPage): array
    {
        if ($project->project_flow === 'linear') {
            return [
                'taskGroups' => collect([$this->buildLinearTaskGroup($project)]),
                'pagination' => [
                    'page' => 1,
                    'next_page' => null,
                    'has_more_pages' => false,
                ],
            ];
        }

        $authUser = auth()->user();
        $page = max($page, 1);
        $perPage = max($perPage, 1);
        $sprintCount = $this->getSprintCount($project);
        $lastPage = max((int) ceil($sprintCount / $perPage), 1);
        $page = min($page, $lastPage);

        $taskGroups = ProjectSprint::query()
            ->where('project_id', $project->id)
            ->with(['projectModule:id,name'])
            ->withCount([
                'tasks' => fn($query) => $query->accessibleBy($authUser),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->forPage($page, $perPage)
            ->get()
            ->values()
            ->map(fn(ProjectSprint $projectSprint, int $index) => $this->mapProjectSprintToTaskGroup(
                $projectSprint,
                (($page - 1) * $perPage) + $index
            ));

        $ungroupedTasks = Task::query()
            ->where('project_id', $project->id)
            ->whereNull('project_sprint_id')
            ->accessibleBy($authUser);
        $ungroupedCount = (clone $ungroupedTasks)->count();
        $includeUngrouped = $ungroupedCount > 0 && ($sprintCount === 0 || $page >= $lastPage);

        if ($includeUngrouped) {
            $taskGroups->push($this->buildUngroupedTaskGroup($ungroupedTasks, $taskGroups->isEmpty() && $page === 1));
        }

        return [
            'taskGroups' => $taskGroups->values(),
            'pagination' => [
                'page' => $page,
                'next_page' => $page < $lastPage ? $page + 1 : null,
                'has_more_pages' => $page < $lastPage,
            ],
        ];
    }

    private function findTaskGroup(Project $project, string $groupKey): ?array
    {
        if ($groupKey === 'all-tasks' && $project->project_flow === 'linear') {
            return $this->buildLinearTaskGroup($project);
        }

        if ($groupKey === 'ungrouped' && $project->project_flow !== 'linear') {
            $ungroupedTasks = Task::query()
                ->where('project_id', $project->id)
                ->whereNull('project_sprint_id')
                ->accessibleBy(auth()->user());

            if (! (clone $ungroupedTasks)->exists()) {
                return null;
            }

            return $this->buildUngroupedTaskGroup($ungroupedTasks, false);
        }

        if (! str_starts_with($groupKey, 'sprint-')) {
            return null;
        }

        $projectSprintId = (int) str_replace('sprint-', '', $groupKey);
        $projectSprint = ProjectSprint::query()
            ->where('project_id', $project->id)
            ->with(['projectModule:id,name'])
            ->withCount([
                'tasks' => fn($query) => $query->accessibleBy(auth()->user()),
            ])
            ->find($projectSprintId);

        if (! $projectSprint) {
            return null;
        }

        $position = ProjectSprint::query()
            ->where('project_id', $project->id)
            ->where(function ($query) use ($projectSprint) {
                $query->where('sort_order', '<', $projectSprint->sort_order)
                    ->orWhere(function ($nestedQuery) use ($projectSprint) {
                        $nestedQuery->where('sort_order', $projectSprint->sort_order)
                            ->where('id', '<=', $projectSprint->id);
                    });
            })
            ->count();

        return $this->mapProjectSprintToTaskGroup($projectSprint, max($position - 1, 0));
    }

    private function resolveTaskGroupLoadedPages(Project $project, ?string $preferredGroupKey, int $perPage): int
    {
        if (! $preferredGroupKey || $project->project_flow === 'linear') {
            return 1;
        }

        if ($preferredGroupKey === 'ungrouped') {
            return max((int) ceil($this->getSprintCount($project) / $perPage), 1);
        }

        if (! str_starts_with($preferredGroupKey, 'sprint-')) {
            return 1;
        }

        $projectSprintId = (int) str_replace('sprint-', '', $preferredGroupKey);
        $targetSprint = ProjectSprint::query()
            ->where('project_id', $project->id)
            ->find($projectSprintId);

        if (! $targetSprint) {
            return 1;
        }

        $position = ProjectSprint::query()
            ->where('project_id', $project->id)
            ->where(function ($query) use ($targetSprint) {
                $query->where('sort_order', '<', $targetSprint->sort_order)
                    ->orWhere(function ($nestedQuery) use ($targetSprint) {
                        $nestedQuery->where('sort_order', $targetSprint->sort_order)
                            ->where('id', '<=', $targetSprint->id);
                    });
            })
            ->count();

        return max((int) ceil($position / $perPage), 1);
    }

    private function getSprintCount(Project $project): int
    {
        return (int) ProjectSprint::query()
            ->where('project_id', $project->id)
            ->count();
    }

    private function buildLinearTaskGroup(Project $project): array
    {
        $allTasks = Task::query()
            ->where('project_id', $project->id)
            ->accessibleBy(auth()->user());
        $taskCount = (clone $allTasks)->count();
        $estimatedSeconds = (int) (clone $allTasks)->sum('estimated_time_seconds');
        $derivedSeconds = (int) (clone $allTasks)->sum('derived_time_seconds');
        $actualSeconds = (int) (clone $allTasks)->sum('actual_time_seconds');

        return [
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
        ];
    }

    private function mapProjectSprintToTaskGroup(ProjectSprint $projectSprint, int $index): array
    {
        $sprintEstimatedSeconds = (int) ($projectSprint->estimated_time_seconds ?? 0);
        $sprintDerivedSeconds = (int) ($projectSprint->derived_time_seconds ?? 0);
        $sprintActualSeconds = (int) ($projectSprint->actual_time_seconds ?? 0);

        return [
            'key' => 'sprint-' . $projectSprint->id,
            'sprint_id' => $projectSprint->id,
            'name' => $projectSprint->name,
            'subtitle' => $projectSprint->projectModule?->name,
            'accent_color' => $projectSprint->color ?: '#22C55E',
            'task_count' => (int) $projectSprint->tasks_count,
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
    }

    private function buildUngroupedTaskGroup($ungroupedTasks, bool $isLatest): array
    {
        $ungroupedEstimatedSeconds = (int) (clone $ungroupedTasks)->sum('estimated_time_seconds');
        $ungroupedDerivedSeconds = (int) (clone $ungroupedTasks)->sum('derived_time_seconds');
        $ungroupedActualSeconds = (int) (clone $ungroupedTasks)->sum('actual_time_seconds');

        return [
            'key' => 'ungrouped',
            'sprint_id' => null,
            'name' => 'Unscheduled Tasks',
            'subtitle' => 'Tasks without a sprint',
            'accent_color' => '#94A3B8',
            'task_count' => (clone $ungroupedTasks)->count(),
            'estimated_seconds' => $ungroupedEstimatedSeconds,
            'estimated_label' => $this->formatSecondsShort($ungroupedEstimatedSeconds),
            'derived_seconds' => $ungroupedDerivedSeconds,
            'derived_label' => $this->formatSecondsShort($ungroupedDerivedSeconds),
            'actual_seconds' => $ungroupedActualSeconds,
            'actual_label' => $this->formatSecondsShort($ungroupedActualSeconds),
            'date_label' => 'No sprint dates',
            'created_label' => null,
            'is_latest' => $isLatest,
            'is_unscheduled' => true,
            'is_linear_group' => false,
        ];
    }

    private function resolveTaskTagIds(array $submittedTags): array
    {
        return collect($submittedTags)
            ->map(fn($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn($value) => filled($value))
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

    private function buildTaskGroupTasksQuery(Project $project, string $groupKey)
    {
        $query = Task::query()
            ->where('project_id', $project->id)
            ->accessibleBy(auth()->user())
            ->with([
                'currentAssignee.primaryAttachment',
                'status',
                'taskType:id,name,code,color',
                'taskMode:id,name,code,color',
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

        return $query;
    }

    private function getTaskGroupTasks(Project $project, string $groupKey): Collection
    {
        return $this->buildTaskGroupTasksQuery($project, $groupKey)->get();
    }

    private function getTaskGroupTaskPage(Project $project, string $groupKey, int $page, int $perPage): array
    {
        $page = max($page, 1);
        $perPage = max($perPage, 1);
        $query = $this->buildTaskGroupTasksQuery($project, $groupKey);
        $total = (clone $query)->count();
        $lastPage = max((int) ceil($total / $perPage), 1);
        $page = min($page, $lastPage);

        return [
            'tasks' => $query
                ->forPage($page, $perPage)
                ->get(),
            'pagination' => [
                'page' => $page,
                'next_page' => $page < $lastPage ? $page + 1 : null,
                'has_more_pages' => $page < $lastPage,
                'total' => $total,
                'per_page' => $perPage,
            ],
        ];
    }

    private function getTaskModalData(Project $project, ?Task $task = null): array
    {
        $taskTypeOptions = TaskType::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color']);
        $taskModeOptions = TaskMode::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color']);
        $taskPriorityOptions = collect(config('project_constants.task_priorities', []))
            ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
            ->values();

        return [
            'canEditTask' => auth()->user()->can('update', $task),
            'isLinearFlow' => $project->project_flow === 'linear',
            'taskStatuses' => TaskStatus::query()
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
                ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('start_date')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id', 'project_module_id', 'name']),
            'assignableUsers' => $project->activeMembers()
                ->orderBy('users.name')
                ->get(['users.id', 'users.name']),
            'parentTaskOptions' => Task::query()
                ->where('project_id', $project->id)
                ->accessibleBy(auth()->user())
                ->when($task, fn($query) => $query->whereKeyNot($task->id))
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

    private function syncTaskAssignmentState(Task $task, ?int $newAssigneeId): void
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

    private function resolveTaskGroupKey(Project $project, Task $task): string
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
        $statusChangeMinDate = $this->getLatestProjectStatusChangeDate($project);
        $stageChangeMinDate = $this->getLatestProjectStageChangeDate($project);

        return [
            'priority' => config('project_constants.project_priorities')[$project->priority] ?? null,
            'projectTimeline' => $timelines['projectTimeline'],
            'customerTimeline' => $timelines['customerTimeline'],
            'projectStatuses' => ProjectStatus::active()->orderBy('sort_order', 'asc')->get(),
            'projectStages' => ProjectStage::active()->orderBy('sort_order', 'asc')->get(),
            'statusChangeMinDate' => $statusChangeMinDate?->toDateString(),
            'statusChangeMinDateLabel' => $statusChangeMinDate ? AppServiceProvider::formatAppDate($statusChangeMinDate) : null,
            'stageChangeMinDate' => $stageChangeMinDate?->toDateString(),
            'stageChangeMinDateLabel' => $stageChangeMinDate ? AppServiceProvider::formatAppDate($stageChangeMinDate) : null,
        ];
    }

    private function renderProjectHeader(Project $project, ProjectServices $service): string
    {
        return view('projects.partials.header', array_merge([
            'project' => $project,
        ], $this->getProjectHeaderData($project, $service)))->render();
    }

    private function getLatestProjectStatusChangeDate(Project $project): ?Carbon
    {
        $latestDate = $project->statusHistories()
            ->reorderDesc('added_at')
            ->orderByDesc('id')
            ->value('added_at');

        return $this->convertStoredTimestampToConfigTimezone($latestDate)?->startOfDay();
    }

    private function getLatestProjectStageChangeDate(Project $project): ?Carbon
    {
        $latestDate = $project->stageHistories()
            ->reorderDesc('added_at')
            ->orderByDesc('id')
            ->value('added_at');

        return $this->convertStoredTimestampToConfigTimezone($latestDate)?->startOfDay();
    }

    private function applyProjectChangeDateValidation($validator, ?string $changeDate, ?Carbon $minimumDate): void
    {
        if (blank($changeDate) || ! $minimumDate) {
            return;
        }

        $validator->after(function ($validator) use ($changeDate, $minimumDate) {
            try {
                $submittedDate = Carbon::parse($changeDate, config('constants.timezone'))->startOfDay();

                if ($submittedDate->lt($minimumDate)) {
                    $validator->errors()->add(
                        'change_date',
                        'The change date must be on or after ' . AppServiceProvider::formatAppDate($minimumDate) . '.'
                    );
                }
            } catch (\Throwable) {
                // The base date validation already reports invalid formats.
            }
        });
    }

    private function convertStoredTimestampToConfigTimezone($value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value, 'UTC')->timezone(config('constants.timezone'));
        } catch (\Throwable) {
            try {
                return Carbon::parse($value, config('constants.timezone'))->timezone(config('constants.timezone'));
            } catch (\Throwable) {
                return null;
            }
        }
    }
}

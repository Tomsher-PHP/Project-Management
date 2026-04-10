<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskNoteRequest;
use App\Http\Requests\TaskCommentRequest;
use App\Http\Requests\TaskQuickStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskMode;
use App\Models\TaskStatusHistory;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Tag;
use App\Models\TaskNote;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class TaskController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Task Management';
        $this->subTitle = 'Manage your tasks';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $accessibleTasksQuery = Task::query()
            ->accessibleBy($user);

        $projectIds = (clone $accessibleTasksQuery)
            ->distinct()
            ->pluck('project_id')
            ->filter();

        $projectModuleIds = (clone $accessibleTasksQuery)
            ->whereNotNull('project_module_id')
            ->distinct()
            ->pluck('project_module_id')
            ->filter();

        $projectSprintIds = (clone $accessibleTasksQuery)
            ->whereNotNull('project_sprint_id')
            ->distinct()
            ->pluck('project_sprint_id')
            ->filter();

        $tasks = (clone $accessibleTasksQuery)
            ->with([
                'project:id,name,project_code,project_flow',
                'projectModule:id,name',
                'projectSprint:id,name',
                'currentAssignee:id,name',
                'status:id,name,color',
                'taskType:id,name,code,color',
                'taskMode:id,name,code,color',
            ])
            ->filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $projects = $projectIds->isEmpty()
            ? collect()
            : Project::query()
            ->whereIn('id', $projectIds)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $projectModules = $projectModuleIds->isEmpty()
            ? collect()
            : ProjectModule::query()
            ->with('project:id,name')
            ->whereIn('id', $projectModuleIds)
            ->orderBy('name', 'asc')
            ->get(['id', 'project_id', 'name'])
            ->map(fn(ProjectModule $projectModule) => (object) [
                'id' => $projectModule->id,
                'project_id' => $projectModule->project_id,
                'name' => $projectModule->project?->name
                    ? $projectModule->project->name . ' / ' . $projectModule->name
                    : $projectModule->name,
            ]);

        $projectSprints = $projectSprintIds->isEmpty()
            ? collect()
            : ProjectSprint::query()
            ->with([
                'project:id,name',
                'projectModule:id,name',
            ])
            ->whereIn('id', $projectSprintIds)
            ->orderBy('name', 'asc')
            ->get(['id', 'project_id', 'project_module_id', 'name'])
            ->map(fn(ProjectSprint $projectSprint) => (object) [
                'id' => $projectSprint->id,
                'project_id' => $projectSprint->project_id,
                'project_module_id' => $projectSprint->project_module_id,
                'name' => collect([
                    $projectSprint->project?->name,
                    $projectSprint->projectModule?->name,
                    $projectSprint->name,
                ])->filter()->implode(' / '),
            ]);

        $statuses = TaskStatus::active()
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'name']);

        $assignees = User::active()
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $types = TaskType::query()
            ->active()
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get(['name', 'code']);
        $modes = TaskMode::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('id', 'asc')
            ->get(['name', 'code']);
        $priorities = config('project_constants.task_priorities', []);
        $taskCreateProjects = Project::query()
            ->accessibleBy($user)
            ->with([
                'projectModules' => fn($query) => $query
                    ->select('id', 'project_id', 'name')
                    ->orderBy('sort_order')
                    ->orderBy('name'),
                'projectSprints' => fn($query) => $query
                    ->select('id', 'project_id', 'project_module_id', 'name')
                    ->orderBy('sort_order')
                    ->orderBy('name'),
                'activeMembers:id,name',
            ])
            ->orderBy('name', 'asc')
            ->get(['id', 'project_code', 'name', 'project_flow', 'default_billable', 'default_task_estimate_seconds']);
        $taskTypeOptions = TaskType::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'color']);
        $taskModeOptions = TaskMode::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'color']);
        $taskPriorityOptions = collect(config('project_constants.task_priorities', []))
            ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
            ->values();
        $tagOptions = Tag::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'color']);
        $taskCreateDependencies = $this->buildTaskCreateDependencies($taskCreateProjects);
        $defaultTaskPriority = $this->getDefaultTaskPriorityValue();
        $defaultTaskDueDate = now(config('constants.timezone'))->addDay()->toDateString();

        return view('tasks.index', compact(
            'tasks',
            'perPage',
            'projects',
            'projectModules',
            'projectSprints',
            'statuses',
            'assignees',
            'types',
            'modes',
            'priorities',
            'taskCreateProjects',
            'taskTypeOptions',
            'taskModeOptions',
            'taskPriorityOptions',
            'tagOptions',
            'taskCreateDependencies',
            'defaultTaskPriority',
            'defaultTaskDueDate'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaskQuickStoreRequest $request, NotificationService $notificationService): JsonResponse
    {
        $validated = $request->validated();
        $project = Project::query()
            ->accessibleBy($request->user())
            ->findOrFail($validated['project_id']);
        $assigneeId = isset($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;
        $isLinearFlow = $project->project_flow === 'linear';
        $selectedModule = $isLinearFlow || empty($validated['project_module_id'])
            ? null
            : ProjectModule::query()
            ->where('project_id', $project->id)
            ->find($validated['project_module_id']);
        $selectedSprint = $isLinearFlow || empty($validated['project_sprint_id'])
            ? null
            : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->find($validated['project_sprint_id']);
        $resolvedModuleId = $isLinearFlow ? null : ($selectedSprint?->project_module_id ?? $selectedModule?->id);
        $resolvedSprintId = $isLinearFlow ? null : $selectedSprint?->id;
        $defaultStatusId = $this->getDefaultTaskStatusIdForFlow($project->project_flow);
        $defaultTaskTypeId = TaskType::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');
        $defaultTaskModeId = TaskMode::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');
        $defaultTaskPriority = $this->getDefaultTaskPriorityValue();
        $defaultTaskEstimateSeconds = $this->getDefaultTaskEstimateSeconds($project);

        $task = DB::transaction(function () use (
            $project,
            $validated,
            $assigneeId,
            $resolvedModuleId,
            $resolvedSprintId,
            $defaultStatusId,
            $defaultTaskTypeId,
            $defaultTaskModeId,
            $defaultTaskPriority,
            $defaultTaskEstimateSeconds
        ) {
            $task = $project->tasks()->create([
                'project_module_id' => $resolvedModuleId,
                'project_sprint_id' => $resolvedSprintId,
                'parent_task_id' => ! empty($validated['parent_task_id']) ? (int) $validated['parent_task_id'] : null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status_id' => ! empty($validated['status_id']) ? (int) $validated['status_id'] : $defaultStatusId,
                'task_type_id' => $validated['task_type_id'] ?? $defaultTaskTypeId,
                'task_mode_id' => $validated['task_mode_id'] ?? $defaultTaskModeId,
                'priority' => $validated['priority'] ?? $defaultTaskPriority,
                'current_assignee_id' => $assigneeId,
                'due_date' => $validated['due_date'] ?? null,
                'estimated_time_seconds' => array_key_exists('estimated_time_minutes', $validated)
                    ? (int) (($validated['estimated_time_minutes'] ?? 0) * 60)
                    : $defaultTaskEstimateSeconds,
                'is_billable' => (bool) ($validated['is_billable'] ?? $project->default_billable),
                'sort_order' => Task::nextSortOrder($project->id, $resolvedSprintId),
            ]);

            if (array_key_exists('tag_ids', $validated)) {
                $task->tags()->sync($this->resolveTaskTagIds($validated['tag_ids'] ?? []));
            }

            return $task;
        });

        $notificationService->sendTaskAssignmentIfNeeded($task, $assigneeId);

        return response()->json([
            'status' => true,
            'message' => 'Task added successfully.',
            'task_id' => $task->id,
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        $task = $this->loadTaskForDetail($task);
        $overviewData = $this->getTaskOverviewData($task);

        return view('tasks.detail-page', [
            'task' => $task,
            'project' => $task->project,
            'taskActivitiesCount' => $this->getTaskActivitiesQuery($task)->count(),
            'taskCommentsCount' => $task->comments()->count(),
            'tabsUrlTemplate' => route('tasks.tabs.show', ['task' => $task, 'tab' => '__TAB__']),
        ] + $overviewData);
    }

    public function activityModal(Task $task): JsonResponse
    {
        $task = $this->loadTaskForDetail($task);

        return response()->json([
            'success' => true,
            'html' => view('tasks.partials.modals.activity-content', [
                'task' => $task,
                'activities' => $this->getRecentTaskActivities($task),
                'viewAllUrl' => route('activity.log', ['task_id' => $task->id]),
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function tab(Request $request, Task $task, string $tab): JsonResponse
    {
        $allowedTabs = ['overview', 'activity', 'notes', 'settings'];
        abort_unless(in_array($tab, $allowedTabs, true), Response::HTTP_NOT_FOUND);

        $task = $this->loadTaskForDetail($task);

        return response()->json([
            'status' => true,
            'tab' => $tab,
            'html' => $this->renderTaskTab($task, $tab),
        ], Response::HTTP_OK);
    }

    public function parentTaskOptions(Request $request, Task $task): JsonResponse
    {
        $task = $this->loadTaskForDetail($task);
        $project = $task->project;
        $sprintId = $request->filled('project_sprint_id') ? (int) $request->input('project_sprint_id') : null;

        $query = Task::query()
            ->where('project_id', $project->id)
            ->accessibleBy($request->user())
            ->whereKeyNot($task->id)
            ->orderBy('name')
            ->orderBy('id');

        if ($project->project_flow === 'linear' || ! $sprintId) {
            $query->whereNull('project_sprint_id');
        } else {
            abort_unless(
                ProjectSprint::query()
                    ->where('project_id', $project->id)
                    ->whereKey($sprintId)
                    ->exists(),
                Response::HTTP_NOT_FOUND
            );

            $query->where('project_sprint_id', $sprintId);
        }

        return response()->json([
            'status' => true,
            'options' => $query->get(['id', 'name', 'code'])->map(function (Task $parentTask) {
                return [
                    'value' => (string) $parentTask->id,
                    'text' => $parentTask->name,
                    'subtype' => $parentTask->code ?: 'Parent task',
                ];
            })->values(),
        ], Response::HTTP_OK);
    }

    public function quickCreateParentOptions(Request $request): JsonResponse
    {
        $projectId = $request->filled('project_id') ? (int) $request->input('project_id') : null;
        $sprintId = $request->filled('project_sprint_id') ? (int) $request->input('project_sprint_id') : null;

        abort_unless($projectId, Response::HTTP_NOT_FOUND);

        $project = Project::query()
            ->accessibleBy($request->user())
            ->findOrFail($projectId);

        $query = Task::query()
            ->where('project_id', $project->id)
            ->accessibleBy($request->user())
            ->orderBy('name')
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
            'options' => $query->get(['id', 'name', 'code'])->map(function (Task $parentTask) {
                return [
                    'value' => (string) $parentTask->id,
                    'text' => $parentTask->name,
                    'subtype' => $parentTask->code ?: 'Parent task',
                ];
            })->values(),
        ], Response::HTTP_OK);
    }

    public function update(TaskUpdateRequest $request, Task $task, NotificationService $notificationService): JsonResponse
    {
        $task = $this->loadTaskForDetail($task);
        $project = $task->project;
        $validated = $request->validated();
        $isLinearFlow = $project->project_flow === 'linear';
        $selectedSprint = $isLinearFlow || empty($validated['project_sprint_id'])
            ? null
            : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->find($validated['project_sprint_id']);
        $resolvedModuleId = $isLinearFlow ? null : ($selectedSprint?->project_module_id ?? null);
        $resolvedSprintId = $isLinearFlow ? null : $selectedSprint?->id;
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
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status_id' => $newStatusId,
                'task_type_id' => $validated['task_type_id'],
                'task_mode_id' => $validated['task_mode_id'],
                'priority' => $validated['priority'],
                'current_assignee_id' => $newAssigneeId,
                'due_date' => $validated['due_date'] ?? null,
                'completed_at' => $validated['completed_at'] ?? null,
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
        $notificationService->sendTaskAssignmentIfNeeded(
            $task,
            $newAssigneeId,
            $previousAssigneeId ?: null
        );
        $task = $this->loadTaskForDetail($task);

        return response()->json([
            'status' => true,
            'message' => 'Task updated successfully.',
            'header_html' => view('tasks.partials.header', [
                'task' => $task,
                'project' => $task->project,
            ] + $this->getTaskOverviewData($task))->render(),
            'overview_html' => $this->renderTaskTab($task, 'overview'),
            'settings_html' => $this->renderTaskTab($task, 'settings'),
        ], Response::HTTP_OK);
    }

    public function commentsModal(Task $task): JsonResponse
    {
        $task = $this->loadTaskForDetail($task);
        $comments = $this->getRecentTaskComments($task);
        $totalComments = $task->comments()->count();

        return response()->json([
            'success' => true,
            'html' => view('tasks.partials.modals.comments-content', [
                'task' => $task,
                'comments' => $comments,
                'totalComments' => $totalComments,
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function storeComment(TaskCommentRequest $request, Task $task): JsonResponse
    {
        $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->validated()['comment'],
        ]);

        $task = $this->loadTaskForDetail($task);
        $comments = $this->getRecentTaskComments($task);
        $totalComments = $task->comments()->count();

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'count' => $totalComments,
            'html' => view('tasks.partials.modals.comments-content', [
                'task' => $task,
                'comments' => $comments,
                'totalComments' => $totalComments,
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function storeNote(TaskNoteRequest $request, Task $task, AttachmentService $attachmentService): JsonResponse
    {
        DB::transaction(function () use ($task, $request, $attachmentService) {
            $validated = $request->validated();

            $note = $task->taskNotes()->create([
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            if (! empty($validated['attachments'])) {
                $projectCode = $task->project?->project_code ?: 'project';
                $taskCode = $task->code ?: ('task-' . $task->id);
                $directory = 'task_files/' . $projectCode . '/' . $taskCode . '/notes';

                foreach ($validated['attachments'] as $file) {
                    $attachmentService->upload(
                        $file,
                        $directory,
                        $note,
                        'public',
                        'public',
                        false,
                        'task_note'
                    );
                }
            }
        });

        $task = $this->loadTaskForDetail($task);
        $taskNotes = $this->getPaginatedTaskNotes($task, 1);

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully.',
            'html' => view('tasks.partials.task-notes-list', [
                'task' => $task,
                'taskNotes' => $taskNotes,
                'canRemove' => auth()->user()->can('update', $task),
                'canCreate' => auth()->user()->can('task.add_notes_files'),
            ])->render(),
            'current_page' => $taskNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function deleteNote(Request $request, Task $task, TaskNote $note, AttachmentService $attachmentService): JsonResponse
    {
        abort_unless($note->task_id === $task->id, Response::HTTP_NOT_FOUND);

        $attachmentService->delete($note->attachments);
        $note->delete();
        $task = $this->loadTaskForDetail($task);
        $taskNotes = $this->getPaginatedTaskNotes($task, (int) $request->input('notes_page', 1));

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully.',
            'html' => view('tasks.partials.task-notes-list', [
                'task' => $task,
                'taskNotes' => $taskNotes,
                'canRemove' => auth()->user()->can('update', $task),
                'canCreate' => auth()->user()->can('task.add_notes_files'),
            ])->render(),
            'current_page' => $taskNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function deleteNoteAttachment(Request $request, Task $task, TaskNote $note, Attachment $attachment, AttachmentService $attachmentService): JsonResponse
    {
        abort_unless($note->task_id === $task->id, Response::HTTP_NOT_FOUND);
        abort_unless(
            $attachment->link_type === TaskNote::class && (int) $attachment->link_id === (int) $note->id,
            Response::HTTP_NOT_FOUND
        );

        $attachmentService->delete(collect([$attachment]));
        $task = $this->loadTaskForDetail($task);
        $taskNotes = $this->getPaginatedTaskNotes($task, (int) $request->input('notes_page', 1));

        return response()->json([
            'success' => true,
            'message' => 'File removed successfully.',
            'html' => view('tasks.partials.task-notes-list', [
                'task' => $task,
                'taskNotes' => $taskNotes,
                'canRemove' => auth()->user()->can('update', $task),
                'canCreate' => auth()->user()->can('task.add_notes_files'),
            ])->render(),
            'current_page' => $taskNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    private function loadTaskForDetail(Task $task): Task
    {
        $task->load([
            'project:id,name,project_code,project_flow,customer_id',
            'project.customer:id,name',
            'projectModule:id,name',
            'projectSprint:id,name,project_module_id',
            'projectSprint.projectModule:id,name',
            'parentTask:id,name,code',
            'currentAssignee:id,name',
            'status:id,name,color',
            'taskType:id,name,code,color',
            'taskMode:id,name,code,color',
            'tags:id,name,color',
            'addedBy:id,name',
            'updatedBy:id,name',
            'currentAssignmentLog.user:id,name',
            'activeTimeLog.user:id,name',
        ]);

        return $task;
    }

    private function renderTaskTab(Task $task, string $tab): string
    {
        return match ($tab) {
            'overview' => view('tasks.partials.tabs.overview', [
                'task' => $task,
                'project' => $task->project,
            ] + $this->getTaskOverviewData($task))->render(),
            'activity' => view('tasks.partials.tabs.activity', [
                'task' => $task,
                'project' => $task->project,
            ] + $this->getTaskActivityData($task))->render(),
            'notes' => view('tasks.partials.tabs.notes', [
                'task' => $task,
                'project' => $task->project,
                'taskNotes' => $this->getPaginatedTaskNotes($task, (int) request()->input('notes_page', 1)),
            ])->render(),
            'settings' => view('tasks.partials.tabs.settings', [
                'task' => $task,
                'project' => $task->project,
            ] + $this->getTaskSettingsData($task))->render(),
            default => '',
        };
    }

    private function getTaskOverviewData(Task $task): array
    {
        $taskPriorityConfig = config('project_constants.task_priorities.' . ($task->priority ?: 'medium')) ?? config('project_constants.task_priorities.medium');

        return [
            'taskStatusHistories' => $task->statusHistories()
                ->with([
                    'status:id,name,color',
                    'addedBy:id,name',
                ])
                ->orderByDesc('added_at')
                ->limit(10)
                ->get(),
            'taskTimeLogs' => $task->timeLogs()
                ->with('user:id,name')
                ->limit(10)
                ->get(),
            'taskTypeLabel' => $task->taskType?->name ?? ucfirst(str_replace('_', ' ', $task->task_type ?: 'feature')),
            'taskModeLabel' => $task->taskMode?->name ?? ucfirst(str_replace('_', ' ', $task->task_mode ?: 'new')),
            'taskPriorityLabel' => $taskPriorityConfig['label'] ?? ucfirst(str_replace('_', ' ', $task->priority ?: 'medium')),
            'taskPriorityConfig' => $taskPriorityConfig,
        ];
    }

    private function getTaskActivityData(Task $task): array
    {
        return [
            'taskActivities' => $this->getRecentTaskActivities($task),
        ];
    }

    private function getTaskActivitiesQuery(Task $task): Builder
    {
        return Activity::query()->where(function (Builder $activityQuery) use ($task) {
            $activityQuery->where(function (Builder $subjectQuery) use ($task) {
                $subjectQuery->where('subject_type', Task::class)
                    ->where('subject_id', $task->id);
            });

            foreach ($this->getTaskActivitySubjectQueries($task) as $subjectType => $subjectIdsQuery) {
                $activityQuery->orWhere(function (Builder $subjectQuery) use ($subjectType, $subjectIdsQuery) {
                    $subjectQuery->where('subject_type', $subjectType)
                        ->whereIn('subject_id', $subjectIdsQuery);
                });
            }
        });
    }

    private function getTaskActivitySubjectQueries(Task $task): array
    {
        return [
            TaskComment::class => TaskComment::query()
                ->where('task_id', $task->id)
                ->select('id'),
            TaskNote::class => TaskNote::query()
                ->where('task_id', $task->id)
                ->select('id'),
            TaskTimeLog::class => TaskTimeLog::query()
                ->where('task_id', $task->id)
                ->select('id'),
        ];
    }

    private function getRecentTaskActivities(Task $task, int $limit = 20): Collection
    {
        return $this->getTaskActivitiesQuery($task)
            ->with([
                'subject',
                'causer' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        User::class => ['primaryAttachment'],
                    ]);
                },
            ])
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function getTaskSettingsData(Task $task): array
    {
        $project = $task->project;

        return [
            'canEditTask' => auth()->user()->can('update', $task),
            'isLinearFlow' => $project->project_flow === 'linear',
            'taskStatuses' => TaskStatus::query()
                ->active()
                ->forFlow($project->project_flow)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'color']),
            'projectSprints' => ProjectSprint::query()
                ->where('project_id', $project->id)
                ->with(['projectModule:id,name'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'project_module_id', 'name']),
            'assignableUsers' => $project->activeMembers()
                ->orderBy('users.name')
                ->get(['users.id', 'users.name']),
            'parentTaskOptions' => Task::query()
                ->where('project_id', $project->id)
                ->accessibleBy(auth()->user())
                ->whereKeyNot($task->id)
                ->orderBy('name')
                ->get(['id', 'name', 'project_sprint_id']),
            'tagOptions' => Tag::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'color']),
            'taskTypeOptions' => TaskType::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn(TaskType $taskType) => ['value' => (string) $taskType->id, 'label' => $taskType->name])
                ->values(),
            'taskModeOptions' => TaskMode::query()
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn(TaskMode $taskMode) => ['value' => (string) $taskMode->id, 'label' => $taskMode->name])
                ->values(),
            'taskPriorityOptions' => collect(config('project_constants.task_priorities', []))
                ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
                ->values(),
        ];
    }

    private function getPaginatedTaskNotes(Task $task, int $page)
    {
        $total = $task->taskNotes()->count();

        if ($total > 0 && $page > (int) ceil($total / 10)) {
            $page = max((int) ceil($total / 10), 1);
        }

        return $task->taskNotes()
            ->with(['attachments', 'addedBy'])
            ->paginate(10, ['*'], 'notes_page', $page)
            ->withPath(route('tasks.edit', $task))
            ->withQueryString();
    }

    private function getRecentTaskComments(Task $task, int $limit = 10): Collection
    {
        return $task->comments()
            ->with('user.primaryAttachment')
            ->latest()
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();
    }

    private function buildTaskCreateDependencies(Collection $projects): array
    {
        $statusOptionsByFlow = TaskStatus::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'flow_type'])
            ->groupBy('flow_type')
            ->map(fn(Collection $statuses) => $statuses->map(fn(TaskStatus $status) => [
                'value' => (string) $status->id,
                'text' => $status->name,
            ])->values())
            ->toArray();
        $defaultStatusIdsByFlow = collect(array_keys(config('project_constants.project_flows', [])))
            ->mapWithKeys(fn(string $flowType) => [$flowType => $this->getDefaultTaskStatusIdForFlow($flowType)]);

        return [
            'projects' => $projects->mapWithKeys(function (Project $project) use ($defaultStatusIdsByFlow) {
                return [(string) $project->id => [
                    'id' => $project->id,
                    'flow' => $project->project_flow,
                    'default_billable' => (bool) $project->default_billable,
                    'default_status_id' => $defaultStatusIdsByFlow[$project->project_flow] ?? null,
                    'default_task_estimate_minutes' => $project->default_task_estimate_seconds !== null
                        ? intdiv((int) $project->default_task_estimate_seconds, 60)
                        : 0,
                    'modules' => $project->projectModules
                        ->map(fn(ProjectModule $projectModule) => [
                            'value' => (string) $projectModule->id,
                            'text' => $projectModule->name,
                        ])
                        ->values(),
                    'sprints' => $project->projectSprints
                        ->map(fn(ProjectSprint $projectSprint) => [
                            'value' => (string) $projectSprint->id,
                            'text' => $projectSprint->name,
                            'project_module_id' => (string) ($projectSprint->project_module_id ?? ''),
                        ])
                        ->values(),
                    'assignees' => $project->activeMembers
                        ->sortBy('name')
                        ->values()
                        ->map(fn(User $user) => [
                            'value' => (string) $user->id,
                            'text' => $user->name,
                        ]),
                ]];
            }),
            'status_options_by_flow' => $statusOptionsByFlow,
            'defaults' => [
                'project_id' => $projects->firstWhere('id', $this->resolveDefaultTaskCreateProjectId($projects))?->id,
                'priority' => $this->getDefaultTaskPriorityValue(),
                'due_date' => now(config('constants.timezone'))->addDay()->toDateString(),
            ],
            'parent_options_url' => route('tasks.quick-create-parent-options'),
        ];
    }

    private function resolveDefaultTaskCreateProjectId(Collection $projects): ?int
    {
        $userId = auth()->id();

        if (! $userId) {
            return null;
        }

        $projectId = Task::query()
            ->where('added_by', $userId)
            ->whereNotNull('project_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('project_id');

        if (! $projectId) {
            return null;
        }

        return $projects->contains('id', $projectId) ? (int) $projectId : null;
    }

    private function getDefaultTaskStatusIdForFlow(?string $flowType): ?int
    {
        if (blank($flowType)) {
            return null;
        }

        return TaskStatus::query()
            ->active()
            ->where('flow_type', $flowType)
            ->orderByDesc('is_default')
            ->orderByRaw('CASE WHEN sort_order = 1 THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
    }

    private function getDefaultTaskPriorityValue(): string
    {
        $priorities = config('project_constants.task_priorities', []);

        if (array_key_exists('medium', $priorities)) {
            return 'medium';
        }

        return (string) (array_key_first($priorities) ?? 'medium');
    }

    private function getDefaultTaskEstimateSeconds(Project $project): int
    {
        return max(0, (int) ($project->default_task_estimate_seconds ?? 0));
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

    private function resolveTaskTagIds(array $submittedTags): array
    {
        return collect($submittedTags)
            ->filter(fn($tag) => filled($tag))
            ->map(function ($tag) {
                if (is_numeric($tag)) {
                    return (int) $tag;
                }

                return $this->firstOrCreateTaskTag((string) $tag)->id;
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }
}

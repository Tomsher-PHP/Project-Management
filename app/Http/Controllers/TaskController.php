<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskNoteRequest;
use App\Http\Requests\TaskCommentRequest;
use App\Http\Requests\TaskQuickStoreRequest;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskStatus;
use App\Models\TaskNote;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\NotificationService;
use App\Services\TaskFilterService;
use App\Services\TaskFormService;
use App\Services\TaskQueryService;
use App\Services\TaskServices;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class TaskController extends Controller
{
    private const KANBAN_STATUS_PAGE_SIZE = 5;

    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Task Management';
        $this->subTitle = 'Manage your tasks';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request, TaskServices $taskServices, TaskFilterService $filterService, TaskFormService $taskFormService)
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));

        $baseQuery = app(TaskQueryService::class)->baseQuery($user);

        $tasks = $taskServices->getList(
            $user,
            $request->all(),
            $perPage
        );

        $filters = $filterService->getFilters($user, $baseQuery);

        $formData = $taskFormService->getCreateData($user);
        $taskCreateProjects = $formData['taskCreateProjects'] ?? collect();
        $taskCreateDependencies = $this->buildTaskCreateDependencies($taskCreateProjects);

        // Preload relations for all tasks in the list to avoid N+1 queries when rendering the list and task cards
        $taskRowRelations = [
            'project:id,name,project_code,project_flow',
            'projectMilestone:id,name',
            'projectSprint:id,name',
            'currentAssignee:id,name',
            'status:id,name,color',
            'taskType:id,name,code,color',
            'taskMode:id,name,code,color',
        ];
        $tasks->getCollection()->each(function (Task $task) use ($taskRowRelations) {
            $this->loadTaskDescendantsForList($task, $taskRowRelations);
        });

        return view('tasks.index', [
            'tasks' => $tasks,
            'perPage' => $perPage,
            'taskCreateDependencies' => $taskCreateDependencies,
            ...$filters,
            ...$formData,
        ]);
    }

    // Kanban view for tasks.
    public function kanbanView(Request $request, TaskServices $taskServices, TaskFilterService $filterService, TaskFormService $taskFormService)
    {
        $selectedFlowType = 'agile';
        $user = $request->user();

        $baseQuery = app(TaskQueryService::class)->baseQuery($user);

        $filters = $filterService->getFilters($user, $baseQuery);

        $formData = $taskFormService->getCreateData($user);

        $taskCreateProjects = $formData['taskCreateProjects'] ?? collect();
        $taskCreateDependencies = $this->buildTaskCreateDependencies($taskCreateProjects);

        $boardStatuses = collect($filters['statuses'] ?? [])
            ->when(
                fn($collection) => $collection->isNotEmpty(),
                fn($collection) => $collection->where('flow_type', $selectedFlowType)
            )->values();

        $tasksByStatus = $taskServices->getKanban(
            $user,
            $request->all(),
            $selectedFlowType,
            $boardStatuses,
            self::KANBAN_STATUS_PAGE_SIZE
        );

        return view('tasks.kanban.kanban-view', array_merge([
            'tasksByStatus' => $tasksByStatus,
            'perPage' => $request->input('per_page'),
            'taskCreateDependencies' => $taskCreateDependencies,
            'boardStatuses' => $boardStatuses,
        ], $filters, $formData));
    }

    // Kanban board ajax loading
    public function kanbanMode(Request $request, TaskServices $taskServices)
    {
        $selectedFlowType = $request->input('flow', 'agile');
        $user = $request->user();

        $boardStatuses = TaskStatus::active()
            ->forFlow($selectedFlowType)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'is_default', 'is_completed']);

        $priorities = config('project_constants.task_priorities', []);

        if ($request->ajax()) {
            if ($request->filled('status_id')) {
                $statusId = (int) $request->input('status_id');
                $page = max((int) $request->input('page', 1), 1);

                $status = $boardStatuses->firstWhere('id', $statusId);
                abort_unless($status, Response::HTTP_NOT_FOUND);

                $column = $taskServices->getKanbanStatusData(
                    $user,
                    $request->all(),
                    $selectedFlowType,
                    $statusId,
                    $page,
                    self::KANBAN_STATUS_PAGE_SIZE
                );

                return response()->json([
                    'status' => true,
                    'html' => view('tasks.kanban._cards', [
                        'tasks' => $column['tasks'],
                        'status' => $status,
                        'priorities' => $priorities,
                    ])->render(),
                    'hasMore' => $column['hasMore'],
                    'nextPage' => $column['nextPage'],
                    'taskIds' => $column['taskIds'],
                    'total' => $column['total'],
                ], Response::HTTP_OK);
            }

            $tasksByStatus = $taskServices->getKanban(
                $user,
                $request->all(),
                $selectedFlowType,
                $boardStatuses,
                self::KANBAN_STATUS_PAGE_SIZE
            );

            return view('tasks.kanban._board', compact('boardStatuses', 'tasksByStatus', 'priorities'))->render();
        }

        $tasksByStatus = $taskServices->getKanban(
            $user,
            $request->all(),
            $selectedFlowType,
            $boardStatuses,
            self::KANBAN_STATUS_PAGE_SIZE
        );

        return view('tasks.kanban.index', compact('boardStatuses', 'tasksByStatus', 'priorities'));
    }

    // Store newly created task with minimal required fields, used for quick create from various places in the app
    public function store(TaskQuickStoreRequest $request, NotificationService $notificationService, TaskServices $taskServices): JsonResponse
    {
        $validated = $request->validated();
        $requestType = ($validated['request_type'] ?? 'assigned') === 'self' ? 'self' : 'assigned';

        $project = Project::query()
            ->accessibleBy($request->user())
            ->findOrFail($validated['project_id']);

        $task = $taskServices->createQuickTask($project, $validated);

        if ($task->isApprovedRequest()) {
            $notificationService->sendTaskAssignmentIfNeeded(
                $task,
                $task->current_assignee_id ? (int) $task->current_assignee_id : null
            );
        } else if ($requestType === 'self') {
            $notificationService->notifyTaskRequestCreated($task);
        }

        return response()->json([
            'status' => true,
            'message' => $requestType === 'self'
                ? 'Task request submitted successfully.'
                : 'Task added successfully.',
            'task_id' => $task->id,
            'request_type' => $task->request_type,
            'request_status' => $task->request_status,
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task, TaskServices $taskServices)
    {
        $task = $this->loadTaskForDetail($task);
        $overviewData = $this->getTaskOverviewData($task);
        $totalSeconds = $taskServices->getTotalTrackedSeconds($task->id, auth()->id());

        return view('tasks.detail-page', [
            'task' => $task,
            'project' => $task->project,
            'taskActivitiesCount' => $this->getTaskActivitiesQuery($task)->count(),
            'taskCommentsCount' => $task->comments()->count(),
            'totalTrackedSeconds' => $totalSeconds,
            'tabsUrlTemplate' => route('tasks.tabs.show', ['task' => $task, 'tab' => '__TAB__']),
        ] + $overviewData + $this->getTaskHeaderData($task, $taskServices));
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
        $allowedTabs = ['overview', 'scope', 'notes', 'history'];
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
            'projectMilestone:id,name',
            'projectSprint:id,name,project_milestone_id',
            'projectSprint.projectMilestone:id,name',
            'parentTask:id,name,code',
            'currentAssignee:id,name',
            'currentAssignee.primaryAttachment',
            'status:id,name,color,type',
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

    private function loadTaskDescendantsForList(Task $task, array $taskRowRelations): void
    {
        if ((int) ($task->child_tasks_count ?? 0) <= 0) {
            $task->setRelation('childTasks', collect());

            return;
        }

        $task->load([
            'childTasks' => fn($query) => $query
                ->with($taskRowRelations)
                ->withCount('childTasks'),
        ]);

        $task->childTasks->each(function (Task $childTask) use ($taskRowRelations) {
            $this->loadTaskDescendantsForList($childTask, $taskRowRelations);
        });
    }

    private function renderTaskTab(Task $task, string $tab): string
    {
        return match ($tab) {
            'overview' => view('tasks.partials.tabs.overview', [
                'task' => $task,
                'project' => $task->project,
            ] + $this->getTaskOverviewData($task))->render(),
            'scope' => $this->renderTaskScopeTab($task),
            'notes' => view('tasks.partials.tabs.notes', [
                'task' => $task,
                'project' => $task->project,
                'taskNotes' => $this->getPaginatedTaskNotes($task, (int) request()->input('notes_page', 1)),
            ])->render(),
            'history' => view('tasks.partials.tabs.history', [
                'task' => $task,
                'project' => $task->project,
            ] + $this->getTaskHistoryData($task))->render(),
            default => '',
        };
    }

    private function renderTaskScopeTab(Task $task): string
    {
        $task->loadMissing('project.scopeFiles.addedBy');

        return view('tasks.partials.tabs.scope', [
            'task' => $task,
            'project' => $task->project,
        ])->render();
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

    private function getTaskHeaderData(Task $task, TaskServices $taskServices): array
    {
        $flowType = $task->project?->project_flow;
        $user = auth()->user();

        return [
            'taskStatusOptions' => blank($flowType)
                ? collect()
                : TaskStatus::query()
                    ->active()
                    ->forFlow($flowType)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'name', 'color', 'is_completed']),
            'canChangeTaskStatus' => $user && $taskServices->isAllowedChangeStatus($task, $user),
            'taskStatusTransitionUrl' => route('tasks.transition-status'),
        ];
    }

    private function getTaskHistoryData(Task $task): array
    {
        $statusRows = $task->statusHistories()
            ->with([
                'status:id,name,color',
                'addedBy:id,name',
            ])
            ->reorder('added_at')
            ->orderBy('id')
            ->get();

        $previousStatus = null;
        $statusHistory = $statusRows
            ->map(function ($history) use (&$previousStatus) {
                $currentStatus = [
                    'label' => $history->status?->name ?? 'No Status',
                    'color' => $history->status?->color ?: '#CBD5E1',
                ];

                $entry = [
                    'from_label' => $previousStatus['label'] ?? 'Start',
                    'from_color' => $previousStatus['color'] ?? '#CBD5E1',
                    'to_label' => $currentStatus['label'],
                    'to_color' => $currentStatus['color'],
                    'changed_at' => $history->added_at,
                    'changed_by' => $history->addedBy?->name ?? 'System',
                    'remarks' => $history->remarks,
                ];

                $previousStatus = $currentStatus;

                return $entry;
            })
            ->reverse()
            ->values();

        $timeLogs = $task->timeLogs()
            ->with([
                'user:id,name',
                'user.primaryAttachment',
                'assignmentLog.user:id,name',
                'assignmentLog.user.primaryAttachment',
            ])
            ->withExists([
                'changeRequests as has_pending_change_request' => fn($query) => $query->where('status', 'pending'),
            ])
            ->reorder('started_at', 'desc')
            ->orderByDesc('id')
            ->get();

        $assignmentHistory = $task->assignmentLogs()
            ->with([
                'user:id,name',
                'user.primaryAttachment',
                'addedBy:id,name',
                'addedBy.primaryAttachment',
                'updatedBy:id,name',
            ])
            ->reorder('assigned_from', 'desc')
            ->orderByDesc('id')
            ->get();

        return [
            'statusHistory' => $statusHistory,
            'timeLogs' => $timeLogs,
            'assignmentHistory' => $assignmentHistory,
            'currentStatus' => [
                'label' => $task->status?->name ?? 'No Status',
                'color' => $task->status?->color ?: '#CBD5E1',
            ],
            'currentAssignee' => $task->currentAssignee?->name ?? 'Unassigned',
            'currentAssigneeUser' => $task->currentAssignee,
            'totalLoggedSeconds' => (int) $timeLogs->sum('duration_seconds'),
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
                    'milestones' => $project->projectMilestones
                        ->reject(fn(ProjectMilestone $projectMilestone) => (bool) ($projectMilestone->is_backlog || $projectMilestone->is_system))
                        ->map(fn(ProjectMilestone $projectMilestone) => [
                            'value' => (string) $projectMilestone->id,
                            'text' => $projectMilestone->name,
                        ])
                        ->values(),
                    'sprints' => $project->projectSprints
                        ->reject(fn(ProjectSprint $projectSprint) => (bool) ($projectSprint->is_backlog || $projectSprint->is_system))
                        ->map(fn(ProjectSprint $projectSprint) => [
                            'value' => (string) $projectSprint->id,
                            'text' => $projectSprint->name,
                            'project_milestone_id' => (string) ($projectSprint->project_milestone_id ?? ''),
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
                'due_date_time' => now(config('constants.timezone'))->addDay()->format('Y-m-d H:i'),
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

    /** Task timer functions */

    public function start(Task $task, TaskServices $taskServices): JsonResponse
    {
        $startRestriction = $taskServices->getStartRestriction($task);

        if ($startRestriction) {
            return response()->json(
                ['message' => $startRestriction['message']],
                $startRestriction['status']
            );
        }

        $taskServices->startTimer($task, auth()->id());

        return response()->json(['message' => 'Timer started'], Response::HTTP_OK);
    }

    public function stop(Request $request, Task $task, TaskServices $taskServices): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $taskServices->isAllowedToStop($task, $user)) {
                return response()->json(['message' => 'Not allowed to stop timer for this task'], Response::HTTP_FORBIDDEN);
            }

            if (
                $user
                && $taskServices->requiresNonAssigneeStopConfirmation($task, $user)
                && ! $request->boolean('confirmed_non_assignee_stop')
            ) {
                $task->loadMissing('currentAssignee:id,name');

                return response()->json([
                    'message' => 'Please confirm before stopping another assignee\'s timer.',
                    'requires_confirmation' => true,
                    'assignee_name' => $task->currentAssignee?->name,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $log = $taskServices->stopTimer($task, $user);

            return response()->json(['message' => 'Timer stopped', 'data' => $log], Response::HTTP_OK);
        } catch (\RuntimeException $e) {

            return response()->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    public function transitionStatus(Request $request, TaskServices $taskServices): JsonResponse
    {
        $validator = validator($request->all(), [
            'status_id' => ['required', 'integer'],
            'moved_task_id' => ['required', 'integer'],
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer'],
            'include_task_detail' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();

        try {
            $statusId = (int) $validated['status_id'];
            $task = $taskServices->transitionStatus(
                auth()->user(),
                (int) $validated['moved_task_id'],
                collect($validated['task_ids'])->map(fn($taskId) => (int) $taskId)->all(),
                $statusId
            );

            $task = $this->loadTaskForDetail($task);
            $response = [
                'success' => true,
                'message' => 'Task status updated successfully.',
                'html' => view('tasks.kanban._card', [
                    'task' => $task,
                    'status' => $task->status,
                    'priorities' => config('project_constants.task_priorities', []),
                ])->render(),
            ];

            if (! empty($validated['include_task_detail'])) {
                $totalSeconds = $taskServices->getTotalTrackedSeconds($task->id, (int) auth()->id());
                $overviewData = $this->getTaskOverviewData($task);
                $historyData = $this->getTaskHistoryData($task);
                $headerData = $this->getTaskHeaderData($task, $taskServices);

                $response['header_html'] = view('tasks.partials.header', [
                    'task' => $task,
                    'project' => $task->project,
                    'totalTrackedSeconds' => $totalSeconds,
                ] + $overviewData + $headerData)->render();

                $response['overview_html'] = view('tasks.partials.tabs.overview', [
                    'task' => $task,
                    'project' => $task->project,
                ] + $overviewData)->render();

                $response['history_html'] = view('tasks.partials.tabs.history', [
                    'task' => $task,
                    'project' => $task->project,
                ] + $historyData)->render();
            }

            return response()->json($response);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {

            info('Error while changing task status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while changing task status.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskNoteRequest;
use App\Http\Requests\TaskCommentRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectSprint;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatusHistory;
use App\Models\ProjectTaskStatus;
use App\Models\Tag;
use App\Models\TaskNote;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $accessibleTasksQuery = ProjectTask::query()
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
                'project:id,name,project_flow',
                'projectModule:id,name',
                'projectSprint:id,name',
                'currentAssignee:id,name',
                'status:id,name,color',
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

        $statuses = ProjectTaskStatus::active()
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'name']);

        $assignees = User::active()
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $types = config('project_constants.task_type', []);
        $modes = config('project_constants.task_mode', []);
        $priorities = config('project_constants.task_priorities', []);

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
            'priorities'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectTask $task)
    {
        $task = $this->loadTaskForDetail($task);
        $overviewData = $this->getTaskOverviewData($task);

        return view('tasks.detail-page', [
            'task' => $task,
            'project' => $task->project,
            'taskCommentsCount' => $task->comments()->count(),
            'tabsUrlTemplate' => route('tasks.tabs.show', ['task' => $task, 'tab' => '__TAB__']),
        ] + $overviewData);
    }

    public function tab(Request $request, ProjectTask $task, string $tab): JsonResponse
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

    public function parentTaskOptions(Request $request, ProjectTask $task): JsonResponse
    {
        $task = $this->loadTaskForDetail($task);
        $project = $task->project;
        $sprintId = $request->filled('project_sprint_id') ? (int) $request->input('project_sprint_id') : null;

        $query = ProjectTask::query()
            ->where('project_id', $project->id)
            ->accessibleBy($request->user())
            ->whereKeyNot($task->id)
            ->orderBy('title')
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
            'options' => $query->get(['id', 'title', 'code'])->map(function (ProjectTask $parentTask) {
                return [
                    'value' => (string) $parentTask->id,
                    'text' => $parentTask->title,
                    'subtype' => $parentTask->code ?: 'Parent task',
                ];
            })->values(),
        ], Response::HTTP_OK);
    }

    public function update(TaskUpdateRequest $request, ProjectTask $task): JsonResponse
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
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status_id' => $newStatusId,
                'task_type' => $validated['task_type'],
                'task_mode' => $validated['task_mode'],
                'priority' => $validated['priority'],
                'current_assignee_id' => $newAssigneeId,
                'start_date' => $validated['start_date'] ?? null,
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

    public function commentsModal(ProjectTask $task): JsonResponse
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

    public function storeComment(TaskCommentRequest $request, ProjectTask $task): JsonResponse
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

    public function storeNote(TaskNoteRequest $request, ProjectTask $task, AttachmentService $attachmentService): JsonResponse
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
            ])->render(),
            'current_page' => $taskNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function deleteNote(Request $request, ProjectTask $task, TaskNote $note, AttachmentService $attachmentService): JsonResponse
    {
        abort_unless($note->project_task_id === $task->id, Response::HTTP_NOT_FOUND);

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
            ])->render(),
            'current_page' => $taskNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function deleteNoteAttachment(Request $request, ProjectTask $task, TaskNote $note, Attachment $attachment, AttachmentService $attachmentService): JsonResponse
    {
        abort_unless($note->project_task_id === $task->id, Response::HTTP_NOT_FOUND);
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
            ])->render(),
            'current_page' => $taskNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    private function loadTaskForDetail(ProjectTask $task): ProjectTask
    {
        $task->load([
            'project:id,name,project_code,project_flow,customer_id',
            'project.customer:id,name',
            'projectModule:id,name',
            'projectSprint:id,name,project_module_id',
            'projectSprint.projectModule:id,name',
            'parentTask:id,title,code',
            'currentAssignee:id,name',
            'status:id,name,color',
            'tags:id,name,color',
            'addedBy:id,name',
            'updatedBy:id,name',
            'currentAssignmentLog.user:id,name',
            'activeTimeLog.user:id,name',
        ]);

        return $task;
    }

    private function renderTaskTab(ProjectTask $task, string $tab): string
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

    private function getTaskOverviewData(ProjectTask $task): array
    {
        $taskTypeConfig = config('project_constants.task_type.' . ($task->task_type ?: 'normal')) ?? config('project_constants.task_type.normal');
        $taskModeConfig = config('project_constants.task_mode.' . ($task->task_mode ?: 'standard')) ?? config('project_constants.task_mode.standard');
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
            'taskTypeLabel' => $taskTypeConfig['label'] ?? ucfirst(str_replace('_', ' ', $task->task_type ?: 'normal')),
            'taskModeLabel' => $taskModeConfig['label'] ?? ucfirst(str_replace('_', ' ', $task->task_mode ?: 'standard')),
            'taskPriorityLabel' => $taskPriorityConfig['label'] ?? ucfirst(str_replace('_', ' ', $task->priority ?: 'medium')),
            'taskPriorityConfig' => $taskPriorityConfig,
        ];
    }

    private function getTaskActivityData(ProjectTask $task): array
    {
        return [
            'taskActivities' => $task->activities()
                ->with('causer')
                ->latest()
                ->limit(20)
                ->get(),
        ];
    }

    private function getTaskSettingsData(ProjectTask $task): array
    {
        $project = $task->project;

        return [
            'canEditTask' => auth()->user()->can('update', $task),
            'isLinearFlow' => $project->project_flow === 'linear',
            'taskStatuses' => ProjectTaskStatus::query()
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
            'parentTaskOptions' => ProjectTask::query()
                ->where('project_id', $project->id)
                ->accessibleBy(auth()->user())
                ->whereKeyNot($task->id)
                ->orderBy('title')
                ->get(['id', 'title', 'project_sprint_id']),
            'tagOptions' => Tag::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'color']),
            'taskTypeOptions' => collect(config('project_constants.task_type', []))
                ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
                ->values(),
            'taskModeOptions' => collect(config('project_constants.task_mode', []))
                ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
                ->values(),
            'taskPriorityOptions' => collect(config('project_constants.task_priorities', []))
                ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
                ->values(),
        ];
    }

    private function getPaginatedTaskNotes(ProjectTask $task, int $page)
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

    private function getRecentTaskComments(ProjectTask $task, int $limit = 10): Collection
    {
        return $task->comments()
            ->with('user.primaryAttachment')
            ->latest()
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();
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
    public function destroy(ProjectTask $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }
}

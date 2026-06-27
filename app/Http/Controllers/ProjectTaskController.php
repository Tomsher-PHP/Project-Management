<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskProjectUpdateRequest;
use App\Http\Requests\TaskQuickStoreRequest;
use App\Http\Requests\TaskMoveRequest;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\NotificationService;
use App\Services\ProjectServices;
use App\Services\TaskRequestServices;
use App\Services\TaskServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectTaskController extends Controller
{
    private const TASK_GROUPS_PER_PAGE = 5;
    private const TASKS_PER_GROUP_PAGE = 5;

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
        $currentTaskId = $request->filled('task_id') ? (int) $request->input('task_id') : null;
        $selectedParentTaskId = $request->filled('parent_task_id') ? (int) $request->input('parent_task_id') : null;
        $currentTask = null;

        if ($currentTaskId) {
            $currentTask = Task::query()
                ->where('project_id', $project->id)
                ->whereKey($currentTaskId)
                ->first();

            abort_unless($currentTask, Response::HTTP_NOT_FOUND);
        }

        $excludedTaskIds = $this->getExcludedParentTaskIds($currentTask);
        $query = Task::query()
            ->where('project_id', $project->id)
            ->when($excludedTaskIds, fn($builder) => $builder->whereNotIn('id', $excludedTaskIds))
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
            $query->whereNull('project_sprint_id');
        }

        $tasks = $query->get(['id', 'name', 'code']);

        if (
            $selectedParentTaskId
            && ! in_array($selectedParentTaskId, $excludedTaskIds, true)
            && ! $tasks->contains('id', $selectedParentTaskId)
        ) {
            $selectedParentTask = Task::query()
                ->where('project_id', $project->id)
                ->whereKey($selectedParentTaskId)
                ->first(['id', 'name', 'code']);

            if ($selectedParentTask) {
                $tasks->push($selectedParentTask);
                $tasks = $tasks
                    ->sortBy(fn(Task $task) => mb_strtolower($task->name) . '|' . str_pad((string) $task->id, 12, '0', STR_PAD_LEFT))
                    ->values();
            }
        }

        return response()->json([
            'status' => true,
            'options' => $tasks->map(function (Task $task) {
                return [
                    'value' => (string) $task->id,
                    'text' => $task->name,
                    'subtype' => $task->code ?: 'Parent task',
                ];
            })->values(),
        ], Response::HTTP_OK);
    }

    public function storeTask(TaskQuickStoreRequest $request, Project $project, TaskServices $taskService): JsonResponse
    {
        $validated = $request->validated();
        $requestType = ($validated['request_type'] ?? 'assigned') === 'self' ? 'self' : 'assigned';
        $task = $taskService->createQuickTask($project, $validated);

        $project->refresh();

        return response()->json([
            'status' => true,
            'message' => $requestType === 'self'
                ? 'Task request submitted successfully.'
                : 'Task added successfully.',
            'html' => $this->renderTasksTab(
                $project,
                $this->resolveTaskGroupKey($project, $task)
            ),
        ], Response::HTTP_OK);
    }

    public function taskModal(Request $request, Project $project, Task $task, TaskRequestServices $taskRequestServices): JsonResponse
    {
        abort_unless((int) $task->project_id === (int) $project->id, Response::HTTP_NOT_FOUND);
        abort_unless($this->canViewTaskModal($task), Response::HTTP_FORBIDDEN);

        $approveMode = $request->boolean('approve_mode');
        $user = $request->user();
        $canApproveRequest = $approveMode && $user && $taskRequestServices->canHandleRequest($user, $task);

        abort_if($approveMode && ! $canApproveRequest, Response::HTTP_FORBIDDEN);

        $task->load([
            'projectMilestone:id,name',
            'projectSprint:id,name,project_milestone_id',
            'parentTask:id,name',
            'currentAssignee.primaryAttachment',
            'status:id,name,color,type,is_completed',
            'tags:id,name,color',
            'addedBy:id,name',
            'updatedBy:id,name',
        ]);

        return response()->json([
            'status' => true,
            'html' => view('projects.partials.tasks.modals.detail-content', array_merge([
                'project' => $project,
                'task' => $task,
                'approveMode' => $approveMode,
            ], $this->getTaskModalData($project, $task, $canApproveRequest)))->render(),
        ], Response::HTTP_OK);
    }

    public function updateTask(TaskProjectUpdateRequest $request, Project $project, Task $task, NotificationService $notificationService, TaskServices $taskService): JsonResponse
    {
        abort_unless((int) $task->project_id === (int) $project->id, Response::HTTP_NOT_FOUND);
        abort_unless($this->canEditTaskModal($task), Response::HTTP_FORBIDDEN);

        $validated = $request->validated();
        $newAssigneeId = ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;
        $previousAssigneeId = (int) ($task->current_assignee_id ?? 0);
        $task = $taskService->updateTask($task, $validated);
        $notificationService->sendTaskAssignmentIfNeeded(
            $task,
            $newAssigneeId,
            $previousAssigneeId ?: null
        );

        return response()->json([
            'status' => true,
            'message' => 'Task updated successfully.',
            'html' => $this->renderTasksTab($project, $this->resolveTaskGroupKey($project, $task)),
        ], Response::HTTP_OK);
    }

    public function moveTask(TaskMoveRequest $request, Project $project, Task $task, ProjectServices $projectService): JsonResponse
    {
        abort_unless((int) $task->project_id === (int) $project->id, Response::HTTP_NOT_FOUND);
        abort_unless(auth()->user()->can('move', $task), Response::HTTP_FORBIDDEN);
        $validated = $request->validated();
        $task = $projectService->moveTaskToSprint(
            $project,
            $task,
            (int) $validated['project_sprint_id']
        );

        return response()->json([
            'status' => true,
            'message' => 'Task moved successfully.',
            'html' => $this->renderTasksTab($project, $this->resolveTaskGroupKey($project, $task)),
        ], Response::HTTP_OK);
    }

    public function destroyTask(Project $project, Task $task): JsonResponse
    {
        abort_unless((int) $task->project_id === (int) $project->id, Response::HTTP_NOT_FOUND);
        abort_unless(auth()->user()->can('delete', $task), Response::HTTP_FORBIDDEN);

        if ($task->childTasks()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Delete the subtasks first before removing this task.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $preferredGroupKey = $this->resolveTaskGroupKey($project, $task);
        $task->delete();

        return response()->json([
            'status' => true,
            'message' => 'Task deleted successfully.',
            'html' => $this->renderTasksTab($project, $preferredGroupKey),
        ], Response::HTTP_OK);
    }

    public function renderTasksTab(Project $project, ?string $preferredGroupKey = null): string
    {
        $isLinearFlow = $project->project_flow === 'linear';
        $taskGroupViewData = $this->getInitialTaskGroupViewData($project, $preferredGroupKey);
        $taskGroups = $taskGroupViewData['taskGroups'];
        $projectMilestones = $isLinearFlow ? collect() : ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->orderForDisplay()
            ->get(['id', 'name', 'is_backlog', 'is_system']);
        $projectSprints = $isLinearFlow ? collect() : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->with(['projectMilestone:id,name'])
            ->orderForDisplay()
            ->get(['id', 'project_milestone_id', 'name', 'is_backlog', 'is_system']);
        $defaultSprintId = $projectSprints->first()?->id;
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
        $defaultTaskStatusId = $this->getDefaultTaskStatusId($project);
        $defaultTaskPriority = $this->getDefaultTaskPriorityValue();
        $defaultTaskEstimateMinutes = $project->default_task_estimate_seconds !== null
            ? intdiv((int) $project->default_task_estimate_seconds, 60)
            : 0;
        $tagOptions = Tag::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $nextTaskTypeSortOrder = ((int) TaskType::max('sort_order')) + 1;
        $nextTaskModeSortOrder = ((int) TaskMode::max('sort_order')) + 1;

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
            'projectMilestones' => $projectMilestones,
            'projectSprints' => $projectSprints,
            'defaultSprintId' => $defaultSprintId,
            'taskGroupsPagination' => $taskGroupViewData['pagination'],
            'taskStatuses' => $taskStatuses,
            'defaultTaskStatusId' => $defaultTaskStatusId,
            'taskTypeOptions' => $taskTypeOptions,
            'taskModeOptions' => $taskModeOptions,
            'nextTaskTypeSortOrder' => $nextTaskTypeSortOrder,
            'nextTaskModeSortOrder' => $nextTaskModeSortOrder,
            'taskPriorityOptions' => $taskPriorityOptions,
            'defaultTaskPriority' => $defaultTaskPriority,
            'defaultTaskEstimateMinutes' => $defaultTaskEstimateMinutes,
            'tagOptions' => $tagOptions,
        ])->render();
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
            ->with(['projectMilestone:id,name'])
            ->withCount([
                'tasks' => fn($query) => $query->accessibleBy(auth()->user()),
            ])
            ->orderForDisplay()
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
            ->with(['projectMilestone:id,name'])
            ->withCount([
                'tasks' => fn($query) => $query->accessibleBy($authUser),
            ])
            ->orderForDisplay()
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
            ->with(['projectMilestone:id,name'])
            ->withCount([
                'tasks' => fn($query) => $query->accessibleBy(auth()->user()),
            ])
            ->find($projectSprintId);

        if (! $projectSprint) {
            return null;
        }

        $position = $this->getOrderedProjectSprintIds($project)
            ->search($projectSprint->id);

        return $this->mapProjectSprintToTaskGroup($projectSprint, max(($position === false ? 0 : $position), 0));
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

        $position = $this->getOrderedProjectSprintIds($project)
            ->search($targetSprint->id);

        return max((int) ceil((($position === false ? 0 : $position) + 1) / $perPage), 1);
    }

    private function getSprintCount(Project $project): int
    {
        return (int) ProjectSprint::query()
            ->where('project_id', $project->id)
            ->count();
    }

    private function getOrderedProjectSprintIds(Project $project): Collection
    {
        return ProjectSprint::query()
            ->where('project_id', $project->id)
            ->orderForDisplay()
            ->pluck('id')
            ->values();
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
            'subtitle' => $projectSprint->projectMilestone?->name,
            'accent_color' => $projectSprint->color ?: '#D1D5DB',
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
        $taskRowRelations = $this->getProjectTaskRowRelations();

        $query = Task::query()
            ->where('project_id', $project->id)
            ->accessibleBy(auth()->user())
            ->whereNull('parent_task_id')
            ->with($taskRowRelations)
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

    private function getTaskGroupTaskPage(Project $project, string $groupKey, int $page, int $perPage): array
    {
        $page = max($page, 1);
        $perPage = max($perPage, 1);
        $query = $this->buildTaskGroupTasksQuery($project, $groupKey);
        $total = (clone $query)->count();
        $lastPage = max((int) ceil($total / $perPage), 1);
        $page = min($page, $lastPage);

        $tasks = $query
            ->forPage($page, $perPage)
            ->get();
        $taskRowRelations = $this->getProjectTaskRowRelations();

        $tasks->each(function (Task $task) use ($taskRowRelations) {
            $this->loadTaskDescendantsForGroup($task, $taskRowRelations);
        });

        return [
            'tasks' => $tasks,
            'pagination' => [
                'page' => $page,
                'next_page' => $page < $lastPage ? $page + 1 : null,
                'has_more_pages' => $page < $lastPage,
                'total' => $total,
                'per_page' => $perPage,
            ],
        ];
    }

    private function loadTaskDescendantsForGroup(Task $task, array $taskRowRelations): void
    {
        if ((int) ($task->child_tasks_count ?? 0) <= 0) {
            $task->setRelation('childTasks', collect());

            return;
        }

        $task->load([
            'childTasks' => fn($query) => $query
                ->with($taskRowRelations)
                ->withCount('childTasks')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        ]);

        $task->childTasks->each(function (Task $childTask) use ($taskRowRelations) {
            $this->loadTaskDescendantsForGroup($childTask, $taskRowRelations);
        });
    }

    private function getProjectTaskRowRelations(): array
    {
        return [
            'currentAssignee.primaryAttachment',
            'status',
            'taskType:id,name,code,color',
            'taskMode:id,name,code,color',
            'tags',
            'projectMilestone:id,name,is_backlog,is_system',
            'projectSprint:id,name,project_milestone_id,is_backlog,is_system',
            'parentTask:id,name',
        ];
    }

    private function getTaskModalData(Project $project, ?Task $task = null, bool $canApproveRequest = false): array
    {
        $selectedStatusId = $task->status_id ?: $this->getDefaultTaskStatusIdForFlow($project->project_flow);
        $selectedtypeId = $task->task_type_id ?: TaskType::query()->active()->where('is_default', true)->value('id');
        $selectedModeId = $task->task_mode_id ?: TaskMode::query()->active()->where('is_default', true)->value('id');
        $currentAssigneeId = $task->current_assignee_id ?? null;

        $taskStatuses = TaskStatus::forForm($selectedStatusId, ['order_by' => 'sort_order'])
            ->forFlow($project->project_flow)
            ->get(['id', 'name', 'color', 'is_default', 'is_completed']);
        $taskTypeOptions = TaskType::forForm($selectedtypeId, ['order_by' => 'sort_order'])
            ->get(['id', 'name', 'color', 'is_default']);
        $taskModeOptions = TaskMode::forForm($selectedModeId, ['order_by' => 'sort_order'])
            ->get(['id', 'name', 'color', 'is_default']);
        $taskPriorityOptions = collect(config('project_constants.task_priorities', []))
            ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label'] ?? ucfirst($key)])
            ->values();

        // Base assignable users on project members with access to the task, ordered by name
        $assignableUsers = $project->activeMembers()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);

        // Include current assignee if missing
        if ($currentAssigneeId && !$assignableUsers->contains('id', $currentAssigneeId)) {
            $currentAssignee = User::query()
                ->where('id', $currentAssigneeId)
                ->first(['id', 'name']);

            if ($currentAssignee) {
                $assignableUsers->push($currentAssignee);
            }
        }

        return [
            'canEditTask' => $task ? ($canApproveRequest || $this->canEditTaskModal($task)) : false,
            'isLinearFlow' => $project->project_flow === 'linear',
            'projectMilestones' => ProjectMilestone::query()
                ->where('project_id', $project->id)
                ->orderForDisplay()
                ->get(['id', 'name']),
            'projectSprints' => ProjectSprint::query()
                ->where('project_id', $project->id)
                ->with(['projectMilestone:id,name'])
                ->orderForDisplay()
                ->get(['id', 'project_milestone_id', 'name']),
            'assignableUsers' => $assignableUsers,
            'parentTaskOptions' => Task::query()
                ->where('project_id', $project->id)
                ->accessibleBy(auth()->user())
                ->when($task, fn($query) => $query->whereNotIn('id', $this->getExcludedParentTaskIds($task)))
                ->orderBy('name')
                ->get(['id', 'name']),
            'tagOptions' => Tag::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'color']),
            'taskStatuses' => $taskStatuses,
            'taskTypeOptions' => $taskTypeOptions,
            'taskModeOptions' => $taskModeOptions,
            'taskPriorityOptions' => $taskPriorityOptions,
        ];
    }

    private function canViewTaskModal(Task $task): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->is_super_admin
            || $user->can('task.view_all_tasks')
            || $user->can('task.view')
            || (int) ($task->current_assignee_id ?? 0) === (int) $user->id;
    }

    private function canEditTaskModal(Task $task): bool
    {
        $user = auth()->user();

        return $user
            && $this->canViewTaskModal($task)
            && ! $task->isRejectedRequest()
            && $user->can('task.edit');
    }

    private function getExcludedParentTaskIds(?Task $task): array
    {
        if (! $task) {
            return [];
        }

        $excludedTaskIds = [(int) $task->id];
        $pendingParentIds = [(int) $task->id];

        while ($pendingParentIds !== []) {
            $childIds = Task::query()
                ->whereIn('parent_task_id', $pendingParentIds)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();

            $pendingParentIds = array_values(array_diff($childIds, $excludedTaskIds));
            $excludedTaskIds = [...$excludedTaskIds, ...$pendingParentIds];
        }

        return $excludedTaskIds;
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

    private function getDefaultTaskStatusId(Project $project): ?int
    {
        return TaskStatus::query()
            ->active()
            ->forFlow($project->project_flow)
            ->where('is_default', 1)
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
}

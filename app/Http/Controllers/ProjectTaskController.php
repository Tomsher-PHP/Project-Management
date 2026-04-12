<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskProjectUpdateRequest;
use App\Http\Requests\TaskQuickStoreRequest;
use App\Http\Requests\TaskMoveRequest;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectSprint;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskStatusHistory;
use App\Models\TaskType;
use App\Providers\AppServiceProvider;
use App\Services\NotificationService;
use App\Services\ProjectServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        $query = Task::query()
            ->where('project_id', $project->id)
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
            'options' => $query->get(['id', 'name', 'code'])->map(function (Task $task) {
                return [
                    'value' => (string) $task->id,
                    'text' => $task->name,
                    'subtype' => $task->code ?: 'Parent task',
                ];
            })->values(),
        ], Response::HTTP_OK);
    }

    public function storeTask(
        TaskQuickStoreRequest $request,
        Project $project,
        NotificationService $notificationService,
        ProjectServices $projectService
    ): JsonResponse {
        $validated = $request->validated();
        $assigneeId = isset($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;
        $isLinearFlow = $project->project_flow === 'linear';

        $defaultStatusId = $this->getDefaultTaskStatusId($project);
        $defaultTaskType = TaskType::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');
        $defaultTaskMode = TaskMode::query()
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
            $defaultStatusId,
            $defaultTaskType,
            $defaultTaskMode,
            $defaultTaskPriority,
            $defaultTaskEstimateSeconds,
            $projectService
        ) {
            $placement = $projectService->finalizeTaskPlacement(
                $project,
                ! empty($validated['project_module_id']) ? (int) $validated['project_module_id'] : null,
                ! empty($validated['project_sprint_id']) ? (int) $validated['project_sprint_id'] : null
            );
            $resolvedModuleId = $placement['project_module_id'];
            $resolvedSprintId = $placement['project_sprint_id'];

            $task = $project->tasks()->create([
                'project_module_id' => $resolvedModuleId,
                'project_sprint_id' => $resolvedSprintId,
                'parent_task_id' => ! empty($validated['parent_task_id']) ? (int) $validated['parent_task_id'] : null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status_id' => ! empty($validated['status_id']) ? (int) $validated['status_id'] : $defaultStatusId,
                'task_type_id' => $validated['task_type_id'] ?? $defaultTaskType,
                'task_mode_id' => $validated['task_mode_id'] ?? $defaultTaskMode,
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
        $project->refresh();

        return response()->json([
            'status' => true,
            'message' => 'Task added successfully.',
            'html' => $this->renderTasksTab(
                $project,
                $isLinearFlow ? 'all-tasks' : ($task->project_sprint_id ? 'sprint-' . $task->project_sprint_id : 'ungrouped')
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
            'parentTask:id,name',
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

    public function updateTask(
        TaskProjectUpdateRequest $request,
        Project $project,
        Task $task,
        NotificationService $notificationService,
        ProjectServices $projectService
    ): JsonResponse {
        abort_unless((int) $task->project_id === (int) $project->id, Response::HTTP_NOT_FOUND);
        abort_unless(auth()->user()->can('update', $task), Response::HTTP_FORBIDDEN);

        $validated = $request->validated();
        $newStatusId = ! empty($validated['status_id']) ? (int) $validated['status_id'] : null;
        $newAssigneeId = ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;
        $previousStatusId = (int) ($task->status_id ?? 0);
        $previousAssigneeId = (int) ($task->current_assignee_id ?? 0);

        DB::transaction(function () use (
            $validated,
            $task,
            $newStatusId,
            $newAssigneeId,
            $previousStatusId,
            $previousAssigneeId,
            $project,
            $projectService
        ) {
            $placement = $projectService->finalizeTaskPlacement(
                $project,
                ! empty($validated['project_module_id']) ? (int) $validated['project_module_id'] : null,
                ! empty($validated['project_sprint_id']) ? (int) $validated['project_sprint_id'] : null
            );
            $resolvedModuleId = $placement['project_module_id'];
            $resolvedSprintId = $placement['project_sprint_id'];

            $task->update([
                'project_module_id' => $resolvedModuleId,
                'project_sprint_id' => $resolvedSprintId,
                'parent_task_id' => ! empty($validated['parent_task_id']) ? (int) $validated['parent_task_id'] : null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status_id' => $newStatusId,
                'task_type_id' => $validated['task_type_id'] ?? null,
                'task_mode_id' => $validated['task_mode_id'] ?? null,
                'priority' => $validated['priority'],
                'current_assignee_id' => $newAssigneeId,
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

    public function moveTask(
        TaskMoveRequest $request,
        Project $project,
        Task $task,
        ProjectServices $projectService
    ): JsonResponse {
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
        $projectModules = $isLinearFlow ? collect() : ProjectModule::query()
            ->where('project_id', $project->id)
            ->orderForDisplay()
            ->get(['id', 'name', 'is_backlog', 'is_system']);
        $projectSprints = $isLinearFlow ? collect() : ProjectSprint::query()
            ->where('project_id', $project->id)
            ->with(['projectModule:id,name'])
            ->orderForDisplay()
            ->get(['id', 'project_module_id', 'name', 'is_backlog', 'is_system']);
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
        $defaultTaskDueDate = now(config('constants.timezone'))->addDay()->toDateString();
        $defaultTaskEstimateMinutes = $project->default_task_estimate_seconds !== null
            ? intdiv((int) $project->default_task_estimate_seconds, 60)
            : 0;
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
            'projectModules' => $projectModules,
            'projectSprints' => $projectSprints,
            'defaultSprintId' => $defaultSprintId,
            'taskGroupsPagination' => $taskGroupViewData['pagination'],
            'taskStatuses' => $taskStatuses,
            'defaultTaskStatusId' => $defaultTaskStatusId,
            'taskTypeOptions' => $taskTypeOptions,
            'taskModeOptions' => $taskModeOptions,
            'taskPriorityOptions' => $taskPriorityOptions,
            'defaultTaskPriority' => $defaultTaskPriority,
            'defaultTaskEstimateMinutes' => $defaultTaskEstimateMinutes,
            'defaultTaskDueDate' => $defaultTaskDueDate,
            'tagOptions' => $tagOptions,
        ])->render();
    }

    private function getDefaultTaskEstimateSeconds(Project $project): int
    {
        return max(0, (int) ($project->default_task_estimate_seconds ?? 0));
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
            ->with(['projectModule:id,name'])
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
            ->with(['projectModule:id,name'])
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
                'parentTask:id,name',
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
                ->orderForDisplay()
                ->get(['id', 'name']),
            'projectSprints' => ProjectSprint::query()
                ->where('project_id', $project->id)
                ->with(['projectModule:id,name'])
                ->orderForDisplay()
                ->get(['id', 'project_module_id', 'name']),
            'assignableUsers' => $project->activeMembers()
                ->orderBy('users.name')
                ->get(['users.id', 'users.name']),
            'parentTaskOptions' => Task::query()
                ->where('project_id', $project->id)
                ->accessibleBy(auth()->user())
                ->when($task, fn($query) => $query->whereKeyNot($task->id))
                ->orderBy('name')
                ->get(['id', 'name']),
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

    private function getDefaultTaskStatusId(Project $project): ?int
    {
        return TaskStatus::query()
            ->active()
            ->forFlow($project->project_flow)
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
}

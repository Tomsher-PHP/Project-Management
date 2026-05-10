<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskStatusHistory;
use App\Models\TaskTimeLog;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskServices
{
    public const KANBAN_SORT_PRIORITY_DESC = 'priority_desc';

    public const KANBAN_SORT_PRIORITY_ASC = 'priority_asc';

    // Initialize task service dependencies
    public function __construct(
        protected ProjectServices $projectServices,
        protected TaskQueryService $queryService,
        protected TaskFilterService $filterService,
        protected NotificationService $notificationService
    ) {}

    // Get paginated task list for the current user
    public function getList(User $user, array $filters, $perPage)
    {
        $query = $this->queryService->baseQuery($user);

        return $query
            ->filter($filters)
            ->sort($filters)
            ->whereNull('parent_task_id')
            ->with($this->relations())
            ->withCount('childTasks')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Get kanban task groups by status for the current user
    public function getKanban(
        User $user,
        array $filters,
        string $flowType,
        $statuses,
        int $perPage = 5,
        array $options = []
    ): array {
        $options['sort'] = $this->resolveKanbanSort($options['sort'] ?? ($filters['sort'] ?? null));

        return collect($statuses)->mapWithKeys(function ($status) use ($user, $filters, $flowType, $perPage, $options) {
            return [
                $status->id => $this->getKanbanStatusData(
                    $user,
                    $filters,
                    $flowType,
                    (int) $status->id,
                    1,
                    $perPage,
                    $options
                ),
            ];
        })->all();
    }

    public function getKanbanStatusData(
        User $user,
        array $filters,
        string $flowType,
        int $statusId,
        int $page = 1,
        int $perPage = 5,
        array $options = []
    ): array {
        $page = max($page, 1);
        $perPage = max($perPage, 1);
        $sort = $this->resolveKanbanSort($options['sort'] ?? ($filters['sort'] ?? null));

        $taskIds = $this->applyKanbanSorting(
            $this->buildKanbanBaseQuery($user, $filters, $flowType, $statusId, $options),
            $sort
        )
            ->where('status_id', $statusId)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->values();

        $total = $taskIds->count();
        $offset = ($page - 1) * $perPage;
        $pageTaskIds = $taskIds->slice($offset, $perPage)->values();

        $tasks = $pageTaskIds->isEmpty()
            ? collect()
            : $this->applyKanbanSorting(
                $this->buildKanbanBaseQuery($user, $filters, $flowType, $statusId, $options),
                $sort
            )
            ->whereIn('id', $pageTaskIds->all())
            ->with($this->relations())
            ->withCount([
                'childTasks',
                'childTasks as completed_child_tasks_count' => function ($query) {
                    $query->where(function ($childTaskQuery) {
                        $childTaskQuery
                            ->whereNotNull('completed_at')
                            ->orWhereHas('status', fn($statusQuery) => $statusQuery->where('is_completed', true));
                    });
                },
            ])
            ->get();

        $hasMore = ($offset + $tasks->count()) < $total;

        return [
            'tasks' => $tasks,
            'taskIds' => $taskIds->all(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'hasMore' => $hasMore,
            'nextPage' => $hasMore ? ($page + 1) : null,
        ];
    }

    // Define related models to eager load for tasks
    private function relations()
    {
        return [
            'project:id,name,project_code,project_flow',
            'projectMilestone:id,name',
            'projectSprint:id,name',
            'currentAssignee:id,name',
            'activeTimeLog:id,task_id,user_id,started_at,is_running',
            'status:id,name,color,type,is_completed',
            'taskType:id,name,code,color',
            'taskMode:id,name,code,color',
        ];
    }

    private function buildKanbanBaseQuery(
        User $user,
        array $filters,
        string $flowType,
        ?int $statusId = null,
        array $options = []
    ) {
        $query = $this->queryService->baseQuery($user)
            ->filter($filters)
            ->whereHas('project', fn($query) => $query->where('project_flow', $flowType))
            ->where('request_status', '!=', 'rejected');

        $recentCompletedDays = (int) ($options['workspace_recent_completed_days'] ?? 0);
        $completedStatusIds = collect($options['completed_status_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (
            $statusId
            && $recentCompletedDays > 0
            && in_array($statusId, $completedStatusIds, true)
        ) {
            $query->whereHas('statusHistories', function ($historyQuery) use ($statusId, $recentCompletedDays) {
                $historyQuery
                    ->where('status_id', $statusId)
                    ->where('added_at', '>=', now()->subDays($recentCompletedDays));
            });
        }

        return $query;
    }

    public function getKanbanSortOptions(): array
    {
        return [
            self::KANBAN_SORT_PRIORITY_DESC => 'Priority: Urgent → Low',
            self::KANBAN_SORT_PRIORITY_ASC => 'Priority: Low → Urgent',
        ];
    }

    public function resolveKanbanSort(?string $sort): ?string
    {
        if (! is_string($sort)) {
            return null;
        }

        return array_key_exists($sort, $this->getKanbanSortOptions()) ? $sort : null;
    }

    private function applyKanbanSorting($query, ?string $sort)
    {
        return match ($sort) {
            self::KANBAN_SORT_PRIORITY_DESC => $query
                ->orderByRaw($this->buildPriorityOrderCase(['urgent', 'high', 'medium', 'low']))
                ->orderBy('sort_order')
                ->orderBy('id'),
            self::KANBAN_SORT_PRIORITY_ASC => $query
                ->orderByRaw($this->buildPriorityOrderCase(['low', 'medium', 'high', 'urgent']))
                ->orderBy('sort_order')
                ->orderBy('id'),
            default => $query
                ->orderBy('sort_order')
                ->orderBy('id'),
        };
    }

    private function buildPriorityOrderCase(array $priorityOrder): string
    {
        $clauses = collect($priorityOrder)
            ->values()
            ->map(fn($priority, $index) => sprintf("WHEN '%s' THEN %d", str_replace("'", "''", (string) $priority), $index + 1))
            ->implode(' ');

        return sprintf('CASE priority %s ELSE %d END', $clauses, count($priorityOrder) + 1);
    }

    // Create a simple task with default placement and tags
    public function createQuickTask(Project $project, array $validated): Task
    {
        return DB::transaction(function () use ($project, $validated) {
            $defaults = $this->resolveDefaults($project);
            $requestType = ($validated['request_type'] ?? 'assigned') === 'self' ? 'self' : 'assigned';
            $assigneeId = $requestType === 'self'
                ? auth()->id()
                : (! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null);
            $placement = $this->finalizePlacement(
                $project,
                ! empty($validated['project_milestone_id']) ? (int) $validated['project_milestone_id'] : null,
                ! empty($validated['project_sprint_id']) ? (int) $validated['project_sprint_id'] : null
            );

            $payload = $this->buildCreatePayload(
                project: $project,
                validated: $validated,
                defaults: $defaults,
                placement: $placement
            );

            $payload['current_assignee_id'] = $assigneeId;
            $payload['request_type'] = $requestType;
            $payload['request_status'] = $requestType === 'self' ? 'pending' : 'approved';
            $payload['approved_by'] = $requestType === 'assigned' ? auth()->id() : null;
            $payload['approved_at'] = $requestType === 'assigned' ? now() : null;
            $payload['rejected_by'] = null;
            $payload['rejected_at'] = null;
            $payload['rejection_reason'] = null;

            $task = $project->tasks()->create($payload);
            $this->recordStatusHistoryIfChanged($task, null, $task->status_id ? (int) $task->status_id : null);

            if (! empty($task->current_assignee_id)) {
                $this->syncTaskAssignmentState($task, $task->current_assignee_id);
            }

            if (array_key_exists('tag_ids', $validated)) {
                $this->syncTags($task, $validated['tag_ids'] ?? []);
            }

            return $task;
        });
    }

    // Update task data and record any status or assignment changes
    public function updateTask(Task $task, array $validated): Task
    {
        return DB::transaction(function () use ($task, $validated) {
            $project = $task->project;
            $previousStatusId = $task->status_id ? (int) $task->status_id : null;
            $previousAssigneeId = $task->current_assignee_id ? (int) $task->current_assignee_id : null;
            $previousMilestoneId = filled($task->project_milestone_id) ? (int) $task->project_milestone_id : null;
            $previousSprintId = filled($task->project_sprint_id) ? (int) $task->project_sprint_id : null;

            $placement = $this->finalizePlacement(
                $project,
                ! empty($validated['project_milestone_id']) ? (int) $validated['project_milestone_id'] : null,
                ! empty($validated['project_sprint_id']) ? (int) $validated['project_sprint_id'] : null
            );

            $payload = $this->buildUpdatePayload(
                task: $task,
                validated: $validated,
                placement: $placement
            );

            $task->update($payload);

            if (
                $previousMilestoneId !== (filled($task->project_milestone_id) ? (int) $task->project_milestone_id : null)
                || $previousSprintId !== (filled($task->project_sprint_id) ? (int) $task->project_sprint_id : null)
            ) {
                $this->projectServices->syncTaskPlacementToDescendants($task);
            }

            if (array_key_exists('tag_ids', $validated)) {
                $this->syncTags($task, $validated['tag_ids'] ?? []);
            }

            $newStatusId = ! empty($validated['status_id']) ? (int) $validated['status_id'] : null;
            $newAssigneeId = ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;
            $newStatus = $this->findTaskStatus($newStatusId);

            $this->recordStatusHistoryIfChanged($task, $previousStatusId, $newStatusId);
            $this->stopRunningTimersIfStatusInactive(
                $task,
                $previousStatusId,
                $newStatus,
                auth()->user()
            );
            $this->syncAssignmentIfChanged($task, $previousAssigneeId, $newAssigneeId);

            return $task->fresh();
        });
    }

    // Determine project milestone and sprint placement for a task
    public function finalizePlacement(Project $project, ?int $milestoneId, ?int $sprintId): array
    {
        return $this->projectServices->finalizeTaskPlacement($project, $milestoneId, $sprintId);
    }

    // Resolve default task values based on project settings
    public function resolveDefaults(Project $project): array
    {
        return [
            'status_id' => $this->getDefaultTaskStatusIdForFlow($project->project_flow),
            'task_type_id' => TaskType::query()
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('id'),
            'task_mode_id' => TaskMode::query()
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('id'),
            'priority' => $this->getDefaultTaskPriorityValue(),
            'estimated_time_seconds' => $this->getDefaultTaskEstimateSeconds($project),
        ];
    }

    // Build payload used to create a new task
    protected function buildCreatePayload(Project $project, array $validated, array $defaults, array $placement): array
    {
        $resolvedSprintId = $placement['project_sprint_id'];

        return [
            'project_milestone_id' => $placement['project_milestone_id'],
            'project_sprint_id' => $resolvedSprintId,
            'parent_task_id' => ! empty($validated['parent_task_id']) ? (int) $validated['parent_task_id'] : null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status_id' => ! empty($validated['status_id']) ? (int) $validated['status_id'] : $defaults['status_id'],
            'task_type_id' => $validated['task_type_id'] ?? $defaults['task_type_id'],
            'task_mode_id' => $validated['task_mode_id'] ?? $defaults['task_mode_id'],
            'priority' => $validated['priority'] ?? $defaults['priority'],
            'current_assignee_id' => ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null,
            'due_date_time' => Task::normalizeTaskDueDateTime($validated['due_date_time'] ?? null),
            'estimated_time_seconds' => array_key_exists('estimated_time_minutes', $validated)
                ? (int) (($validated['estimated_time_minutes'] ?? 0) * 60)
                : $defaults['estimated_time_seconds'],
            'is_billable' => (bool) ($validated['is_billable'] ?? $project->default_billable),
            'sort_order' => Task::nextSortOrder($project->id, $resolvedSprintId),
        ];
    }

    // Build payload used to update an existing task
    protected function buildUpdatePayload(Task $task, array $validated, array $placement): array
    {
        return [
            'project_milestone_id' => $placement['project_milestone_id'],
            'project_sprint_id' => $placement['project_sprint_id'],
            'parent_task_id' => ! empty($validated['parent_task_id']) ? (int) $validated['parent_task_id'] : null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status_id' => ! empty($validated['status_id']) ? (int) $validated['status_id'] : null,
            'task_type_id' => $validated['task_type_id'],
            'task_mode_id' => $validated['task_mode_id'],
            'priority' => $validated['priority'],
            'current_assignee_id' => ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null,
            'due_date_time' => Task::normalizeTaskDueDateTime($validated['due_date_time'] ?? null),
            'completed_at' => array_key_exists('completed_at', $validated) ? ($validated['completed_at'] ?? null) : $task->completed_at,
            'estimated_time_seconds' => (int) (($validated['estimated_time_minutes'] ?? 0) * 60),
            'is_billable' => (bool) ($validated['is_billable'] ?? false),
            'sort_order' => ! empty($validated['sort_order']) ? (int) $validated['sort_order'] : $task->sort_order,
        ];
    }

    // Sync task tag relationships from submitted tag IDs or names
    public function syncTags(Task $task, array $submittedTags): void
    {
        $task->tags()->sync($this->resolveTaskTagIds($submittedTags));
    }

    // Record a task status change in history if it changed
    public function recordStatusHistoryIfChanged(Task $task, ?int $previousStatusId, ?int $newStatusId): void
    {
        if ($newStatusId && $newStatusId !== $previousStatusId) {
            TaskStatusHistory::create([
                'task_id' => $task->id,
                'status_id' => $newStatusId,
            ]);
        }
    }

    // Sync assignment state only when the assignee changes
    public function syncAssignmentIfChanged(Task $task, ?int $previousAssigneeId, ?int $newAssigneeId): void
    {
        if ($newAssigneeId !== $previousAssigneeId) {
            $this->syncTaskAssignmentState($task, $newAssigneeId);
        }
    }

    // Update assignment logs when a task is reassigned
    public function syncTaskAssignmentState(Task $task, ?int $newAssigneeId): void
    {
        $currentLog = $task->currentAssignmentLog()->first();
        $now = now();

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

    // Convert submitted tags into task tag IDs
    public function resolveTaskTagIds(array $submittedTags): array
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

    // Get or create a tag record by name for task assignment
    protected function firstOrCreateTaskTag(string $name): Tag
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

    // Find the default task status id for a project flow
    protected function getDefaultTaskStatusIdForFlow(?string $flowType): ?int
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

    // Get the default task priority label from config
    protected function getDefaultTaskPriorityValue(): string
    {
        $priorities = config('project_constants.task_priorities', []);

        if (array_key_exists('medium', $priorities)) {
            return 'medium';
        }

        return (string) (array_key_first($priorities) ?? 'medium');
    }

    // Get the default estimated seconds for the task
    protected function getDefaultTaskEstimateSeconds(Project $project): int
    {
        return max(0, (int) ($project->default_task_estimate_seconds ?? 0));
    }

    /**================= Start/Stop Timer related methods ================= */

    // Start a timer for the given task and user
    public function startTimer(Task $task, int $userId)
    {
        return DB::transaction(function () use ($task, $userId) {

            // Check if already running
            $running = TaskTimeLog::where('task_id', $task->id)
                ->where('user_id', $userId)
                ->where('is_running', 1)
                ->first();

            if ($running) {
                throw new \Exception('Timer already running');
            }

            $assignment = TaskAssignmentLog::where('task_id', $task->id)
                ->where('user_id', $userId)
                ->where('is_current', 1)
                ->first();

            if (!$assignment) {
                throw new \Exception('Assignment not found');
            }

            return TaskTimeLog::create([
                'task_id' => $task->id,
                'user_id' => $userId,
                'task_assignment_log_id' => $assignment->id,
                'started_at' => now(),
                'is_running' => 1,
                'is_approved' => ($task->request_status === 'approved'),
            ]);
        });
    }

    // Stop the currently running timer and record duration
    public function stopTimer(Task $task, User $actor)
    {
        return DB::transaction(function () use ($task, $actor) {
            $task->loadMissing([
                'project:id,name',
                'currentAssignee:id,name',
            ]);

            $stoppedByNonAssignee = $this->requiresNonAssigneeStopConfirmation($task, $actor);
            $log = TaskTimeLog::where('task_id', $task->id)->where('is_running', 1)->latest()->first();

            if (!$log) {
                throw new \RuntimeException('Timer not found');
            }

            $duration = $log->started_at->diffInSeconds(now());

            // Update time log
            $log->update([
                'ended_at' => now(),
                'duration_seconds' => $duration,
                'is_running' => 0,
            ]);

            // Update assignment log
            TaskAssignmentLog::where('id', $log->task_assignment_log_id)->increment('worked_time_seconds', $duration);

            // Update task total time
            $task->increment('actual_time_seconds', $duration);

            if ($stoppedByNonAssignee) {
                $this->notificationService->notifyTaskTimerStoppedByOtherUser($task, $actor);
            }

            return $log;
        });
    }

    // Check whether current user can start timer on this task
    public function isAllowedToStart(Task $task): bool
    {
        return $this->getStartRestriction($task) === null;
    }

    // Get restriction message and status if user cannot start timer on this task, otherwise return null
    public function getStartRestriction(Task $task, ?User $user = null): ?array
    {
        $user ??= auth()->user();

        if (! $user) {
            return $this->buildTaskStartRestriction(
                'Not allowed to start timer for this task.',
                Response::HTTP_FORBIDDEN
            );
        }

        if ($task->isRejectedRequest()) {
            return $this->buildTaskStartRestriction(
                'Cannot start timer for rejected task request.',
                Response::HTTP_FORBIDDEN
            );
        }

        if (! $this->isTimerAllowedForTaskStatus($task)) {
            return $this->buildTaskStartRestriction(
                'Move this task to an active status before starting the timer.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($task->current_assignee_id === null) {
            return $this->buildTaskStartRestriction(
                'Assign this task before starting the timer.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ((int) $task->current_assignee_id !== (int) $user->id) {
            return $this->buildTaskStartRestriction(
                'Only the current assignee can start the timer for this task.',
                Response::HTTP_FORBIDDEN
            );
        }

        $runningTimer = TaskTimeLog::query()
            ->with('task:id,name,code')
            ->where('user_id', $user->id)
            ->where('is_running', 1)
            ->latest('started_at')
            ->first();

        if (! $runningTimer) {
            return null;
        }

        if ((int) $runningTimer->task_id === (int) $task->id) {
            return $this->buildTaskStartRestriction(
                'Timer already running for this task.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $runningTaskLabel = $runningTimer->task?->name
            ?: ($runningTimer->task?->code ?: ('task #' . $runningTimer->task_id));

        return $this->buildTaskStartRestriction(
            "Please stop the timer for '{$runningTaskLabel}' before starting another task.",
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    private function buildTaskStartRestriction(string $message, int $status): array
    {
        return [
            'message' => $message,
            'status' => $status,
        ];
    }

    // Check whether current user can stop timer on this task
    public function isAllowedToStop(Task $task, ?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        if (! $user) {
            return false;
        }

        // if task is not assigned, no one can stop timer
        if ($task->current_assignee_id === null) {
            return false;
        }

        // Super admin
        if ($user->is_super_admin) {
            return true;
        }

        // Current assignee
        if ($task->current_assignee_id === $user->id) {
            return true;
        }

        // Load assignee details safely
        $assignee = $task->currentAssignee?->loadMissing('details');

        if (!$assignee || !$assignee->details) {
            return false;
        }

        // Manager of assignee
        if ($assignee->details->manager_id === $user->id) {
            return true;
        }

        // Reporter of assignee (optional, if needed)
        if ($assignee->details->reporter_id === $user->id) {
            return true;
        }

        // Allow if project team leader
        if ($task->project->teamLeader->id === $user->id) {
            return true;
        }

        return false;
    }

    public function requiresNonAssigneeStopConfirmation(Task $task, User $user): bool
    {
        if ($task->current_assignee_id === null) {
            return false;
        }

        return (int) $task->current_assignee_id !== (int) $user->id;
    }

    public function isAllowedChangeStatus(Task $task, User $user): bool
    {
        return ! $task->isRejectedRequest() && $this->isAllowedToStop($task, $user);
    }

    // Calculate total completed tracked seconds for a user on a task
    public function getTotalTrackedSeconds(int $taskId, int $userId): int
    {
        // 1. Sum completed durations
        return TaskTimeLog::where('task_id', $taskId)
            ->where('user_id', $userId)
            ->where('is_running', 0)
            ->sum('duration_seconds');
    }

    public function transitionStatus(User $user, int $movedTaskId, array $taskIds, int $statusId): Task
    {
        return DB::transaction(function () use ($user, $movedTaskId, $taskIds, $statusId) {
            $movedTask = Task::query()
                ->accessibleBy($user)
                ->find($movedTaskId);

            if (! $movedTask) {
                throw new \Exception('Task not found');
            }

            $isSameStatusReorder = $movedTask->status_id === $statusId;

            if (! $isSameStatusReorder && ! $this->isAllowedChangeStatus($movedTask, $user)) {
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                    Response::HTTP_FORBIDDEN,
                    'Not allowed to change status for this task'
                );
            }

            $movedTask->loadMissing('project:id,project_flow');

            $newStatus = TaskStatus::query()
                ->active()
                ->whereKey($statusId)
                ->when(
                    filled($movedTask->project?->project_flow),
                    fn($query) => $query->forFlow($movedTask->project->project_flow)
                )
                ->first();

            if (! $newStatus) {
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'The selected task status is invalid.'
                );
            }

            // Bulk fetch
            $tasks = Task::query()
                ->accessibleBy($user)
                ->whereIn('id', $taskIds)
                ->get()
                ->keyBy('id');

            foreach ($taskIds as $index => $taskId) {

                $task = $tasks[$taskId] ?? null;
                if (! $task) continue;

                $previousStatusId = $task->status_id;

                $task->update([
                    'status_id' => $newStatus->id,
                    'sort_order' => $index
                ]);

                if ($task->id === $movedTask->id && $previousStatusId !== $newStatus->id) {
                    $this->recordStatusHistoryIfChanged(
                        $task,
                        $previousStatusId,
                        $newStatus->id
                    );
                    $this->stopRunningTimersIfStatusInactive(
                        $task,
                        $previousStatusId,
                        $newStatus,
                        $user
                    );

                    app(NotificationService::class)->notifyTaskStatusChanged(
                        $task,
                        $user,
                        $this->getStatusName($previousStatusId),
                        $newStatus->name
                    );
                }
            }

            return Task::query()
                ->with($this->relations())
                ->findOrFail($movedTaskId);
        });
    }

    private function getStatusName(int $statusId): string
    {
        return TaskStatus::find($statusId)?->name ?? 'Unknown';
    }

    public function isTimerAllowedForTaskStatus(Task $task): bool
    {
        return $this->getTaskStatusType($task) === 'active';
    }

    private function getTaskStatusType(Task $task): ?string
    {
        if (! filled($task->status_id)) {
            return null;
        }

        return TaskStatus::query()
            ->whereKey($task->status_id)
            ->value('type');
    }

    private function findTaskStatus(?int $statusId): ?TaskStatus
    {
        if (! $statusId) {
            return null;
        }

        return TaskStatus::query()
            ->whereKey($statusId)
            ->first(['id', 'name', 'type']);
    }

    private function stopRunningTimersIfStatusInactive(Task $task, ?int $previousStatusId, ?TaskStatus $newStatus, ?User $actor = null): void
    {
        if (! $newStatus || $newStatus->type === 'active' || $previousStatusId === (int) $newStatus->id) {
            return;
        }

        if (! $this->stopAllRunningTimers($task)) {
            return;
        }

        if ($actor) {
            $this->notificationService->notifyTaskTimerStoppedBecauseStatusChanged($task, $actor, $newStatus->name ?? 'the updated status');
        }
    }

    private function stopAllRunningTimers(Task $task): bool
    {
        $runningLogs = TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->where('is_running', true)
            ->get();

        if ($runningLogs->isEmpty()) {
            return false;
        }

        $now = now();
        $totalDuration = 0;

        foreach ($runningLogs as $log) {
            $duration = max(0, $log->started_at?->diffInSeconds($now) ?? 0);

            $log->update([
                'ended_at' => $now,
                'duration_seconds' => $duration,
                'is_running' => false,
            ]);

            if ($log->task_assignment_log_id) {
                TaskAssignmentLog::query()
                    ->whereKey($log->task_assignment_log_id)
                    ->increment('worked_time_seconds', $duration);
            }

            $totalDuration += $duration;
        }

        if ($totalDuration > 0) {
            $task->increment('actual_time_seconds', $totalDuration);
        }

        return true;
    }
}

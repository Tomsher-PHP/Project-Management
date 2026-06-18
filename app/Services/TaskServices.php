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
use App\Providers\AppServiceProvider;
use App\Services\Task\RunningTaskNavbarService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskServices
{
    public const KANBAN_SORT_RECOMMENDED = 'recommended';

    public const KANBAN_SORT_PRIORITY_DESC = 'priority_desc';

    public const KANBAN_SORT_PRIORITY_ASC = 'priority_asc';

    private const TASK_TIMELINE_FIELDS = [
        'due_date_time' => 'Due Date',
        'estimated_time_seconds' => 'Estimated Time',
    ];

    // Initialize task service dependencies
    public function __construct(
        protected ProjectServices $projectServices,
        protected TaskQueryService $queryService,
        protected TaskFilterService $filterService,
        protected NotificationService $notificationService,
        protected HandoffServices $handoffServices,
        protected RunningTaskNavbarService $runningTaskNavbarService
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
    public function getKanban(User $user, array $filters, string $flowType, $statuses, int $perPage = 5, array $options = []): array
    {
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

    public function getKanbanStatusData(User $user, array $filters, string $flowType, int $statusId, int $page = 1, int $perPage = 5, array $options = []): array
    {
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

        $this->hydrateKanbanTimerState(
            $tasks,
            $options['timer_user'] ?? 'current_assignee'
        );

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

    public function getTaskDisplayData(Task $task): array
    {
        $taskPriorityConfig = config('project_constants.task_priorities.' . ($task->priority ?: 'medium'))
            ?? config('project_constants.task_priorities.medium');

        return [
            'taskTypeLabel' => $task->taskType?->name ?? ucfirst(str_replace('_', ' ', $task->task_type ?: 'feature')),
            'taskModeLabel' => $task->taskMode?->name ?? ucfirst(str_replace('_', ' ', $task->task_mode ?: 'new')),
            'taskPriorityLabel' => $taskPriorityConfig['label'] ?? ucfirst(str_replace('_', ' ', $task->priority ?: 'medium')),
            'taskPriorityConfig' => $taskPriorityConfig,
        ];
    }

    public function getTaskOverviewData(Task $task): array
    {
        $task->loadMissing([
            'project:id,name,project_code,project_flow',
            'projectMilestone:id,name',
            'projectSprint:id,name,project_milestone_id',
            'parentTask:id,name,code',
            'currentAssignee:id,name',
            'status:id,name,color,type,is_completed',
            'taskType:id,name,code,color',
            'taskMode:id,name,code,color',
            'tags:id,name,color',
            'addedBy:id,name',
            'updatedBy:id,name',
            'currentAssignmentLog.user:id,name',
            'childTasks' => fn($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id')
                ->with([
                    'status:id,name,color,type,is_completed',
                    'currentAssignee:id,name',
                ]),
        ]);

        $displayData = $this->getTaskDisplayData($task);

        return $displayData + [
            'description' => (string) ($task->description ?? ''),
            'taskDetails' => [
                [
                    'label' => 'Assignee',
                    'value' => $task->currentAssignee?->name ?? 'Unassigned',
                ],
                [
                    'label' => 'Parent Task',
                    'value' => $task->parentTask?->name ?? '--',
                    'url' => $task->parentTask ? route('tasks.edit', $task->parentTask) : null,
                ],
                [
                    'label' => 'Status',
                    'value' => $task->status?->name ?? 'No status',
                    'color' => $task->status?->color,
                ],
                [
                    'label' => 'Priority',
                    'value' => $displayData['taskPriorityLabel'],
                    'badge_class' => $displayData['taskPriorityConfig']['bg_class'] ?? 'bg-primary',
                ],
            ],
            'contextItems' => [
                [
                    'label' => 'Project',
                    'value' => $task->project
                        ? view('components.project-flow-icon', [
                            'flow' => $task->project->project_flow,
                            'size' => 'sm',
                        ])->render() . '<span>' . e($task->project->name) . '</span>'
                        : '--',
                    'url' => $task->project ? route('projects.edit', $task->project) : null,
                    'is_html' => true,
                ],
                [
                    'label' => 'Milestone',
                    'value' => $task->projectMilestone?->name ?? '--',
                    'url' => ($task->project && $task->projectMilestone)
                        ? route('projects.edit', ['project' => $task->project, 'tab' => 'milestones', 'milestone' => $task->projectMilestone->id])
                        : null,
                ],
                [
                    'label' => 'Sprint',
                    'value' => $task->projectSprint?->name ?? '--',
                    'url' => ($task->project && $task->projectSprint)
                        ? route('projects.edit', [
                            'project' => $task->project,
                            'tab' => 'milestones',
                            'milestone' => $task->projectSprint->project_milestone_id ?: $task->project_milestone_id,
                            'sprint' => $task->projectSprint->id,
                        ])
                        : null,
                ],
                [
                    'label' => 'Created',
                    'value' => trim(($task->addedBy?->name ?? '--') . ' at ' . (
                        $task->created_at
                        ? \App\Providers\AppServiceProvider::formatAppDateTime($task->created_at)
                        : '--'
                    )),
                ],
                [
                    'label' => 'Updated',
                    'value' => $task->updatedBy?->name
                        ? trim($task->updatedBy->name . (
                            $task->updated_at
                                ? ' at ' . \App\Providers\AppServiceProvider::formatAppDateTime($task->updated_at)
                                : ''
                        ))
                        : '--',
                ],
            ],
            'tags' => $task->tags,
            'subtasks' => $task->childTasks,
            'timeComparison' => $this->buildTaskTimeComparison($task),
            'is_billable' => (bool) $task->is_billable,
        ];
    }

    public function updateTaskDescription(Task $task, ?string $description): Task
    {
        $task->update([
            'description' => filled($description) ? $description : null,
        ]);

        return $task->fresh();
    }

    // Define related models to eager load for tasks
    private function relations()
    {
        return [
            'project:id,name,project_code,project_flow,customer_id',
            'projectMilestone:id,name',
            'projectSprint:id,name',
            'currentAssignee:id,name',
            'activeTimeLog:id,task_id,user_id,started_at,is_running',
            'status:id,name,color,type,is_completed',
            'taskType:id,name,code,color',
            'taskMode:id,name,code,color',
        ];
    }

    public function hydrateKanbanTimerState($tasks, $timerUser): void
    {
        if ($tasks->isEmpty()) {
            return;
        }

        if ($timerUser === 'current_assignee') {
            $timerStates = $this->runningTaskNavbarService->getTaskStatesForTaskAssignees($tasks);
        } else {
            $timerUserId = $timerUser instanceof User
                ? (int) $timerUser->id
                : (is_numeric($timerUser) ? (int) $timerUser : null);

            $timerStates = $this->runningTaskNavbarService->getTaskStatesForUser($timerUserId, $tasks);
        }

        foreach ($tasks as $task) {
            $timerState = $timerStates[(int) $task->id] ?? null;

            if (! is_array($timerState)) {
                continue;
            }

            $task->setAttribute('kanban_timer_tracked_seconds', (int) ($timerState['trackedSeconds'] ?? 0));
            $task->setAttribute('kanban_timer_elapsed_seconds', (int) ($timerState['elapsedSeconds'] ?? 0));
            $task->setAttribute('kanban_timer_current_seconds', (int) ($timerState['currentSeconds'] ?? 0));
            $task->setAttribute('kanban_timer_estimated_seconds', (int) ($timerState['estimatedSeconds'] ?? 0));
            $task->setAttribute('kanban_timer_time_color_class', (string) ($timerState['timeColorClass'] ?? 'text-bgray-700 dark:text-bgray-300'));
            $task->setAttribute('kanban_timer_started_at_iso', $timerState['runningTimeLog']?->started_at?->toISOString());
            $task->setAttribute('kanban_timer_is_running', $timerState['runningTimeLog'] !== null);
        }
    }

    private function buildKanbanBaseQuery(User $user, array $filters, string $flowType, ?int $statusId = null, array $options = [])
    {
        $query = $this->queryService->baseQuery($user)
            ->filter($filters)
            ->whereHas('project', fn($query) => $query->where('project_flow', $flowType))
            ->where('request_status', '!=', 'rejected');

        $workspaceUserId = (int) ($options['workspace_user_id'] ?? 0);

        if ($workspaceUserId > 0) {
            $query->where('current_assignee_id', $workspaceUserId);
        }

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
            self::KANBAN_SORT_RECOMMENDED => 'Recommended',
            self::KANBAN_SORT_PRIORITY_DESC => 'Priority: Urgent to Low',
            self::KANBAN_SORT_PRIORITY_ASC => 'Priority: Low to Urgent',
        ];
    }

    public function resolveKanbanSort(?string $sort): string
    {
        if (! is_string($sort)) {
            return self::KANBAN_SORT_RECOMMENDED;
        }

        return array_key_exists($sort, $this->getKanbanSortOptions())
            ? $sort
            : self::KANBAN_SORT_RECOMMENDED;
    }

    private function applyKanbanSorting($query, string $sort)
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

            if (!empty($validated['handoff_request_id'])) {
                $this->handoffServices->markAsAssigned(
                    (int) $validated['handoff_request_id'],
                    $task,
                    auth()->user()
                );
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
            $originalTimelineValues = $task->only(array_keys(self::TASK_TIMELINE_FIELDS));

            $newAssigneeId = ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;

            if ($previousAssigneeId !== $newAssigneeId) {
                $hasRunningTimer = TaskTimeLog::where('task_id', $task->id)
                    ->where('is_running', 1)
                    ->exists();

                if ($hasRunningTimer && $actor = auth()->user()) {
                    $this->stopTimer($task, $actor);
                }
            }

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

            $task->fill($payload);
            $timelineChanges = $this->buildTaskTimelineChanges($task, $originalTimelineValues);
            $task->save();

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
            $newStatus = $this->findTaskStatus($newStatusId);

            $this->recordStatusHistoryIfChanged($task, $previousStatusId, $newStatusId);
            $this->stopRunningTimersIfStatusInactive(
                $task,
                $previousStatusId,
                $newStatus,
                auth()->user()
            );
            $this->syncAssignmentIfChanged($task, $previousAssigneeId, $newAssigneeId);

            $updatedTask = $task->fresh();

            if ($actor = auth()->user()) {
                if ($previousAssigneeId !== $newAssigneeId) {
                    $this->notificationService->notifyTaskAssigneeChanged(
                        $updatedTask,
                        $actor,
                        $previousAssigneeId,
                        $newAssigneeId
                    );
                }

                if ($timelineChanges !== []) {
                    $this->notificationService->notifyTaskTimelineChanged(
                        $updatedTask,
                        $actor,
                        $timelineChanges
                    );
                }
            }

            return $updatedTask;
        });
    }

    private function buildTaskTimelineChanges(Task $task, array $originalTimelineValues): array
    {
        return collect(self::TASK_TIMELINE_FIELDS)
            ->filter(fn($label, $field) => $task->isDirty($field))
            ->map(function ($label, $field) use ($task, $originalTimelineValues) {
                return [
                    'field' => $label,
                    'old' => $this->formatTaskTimelineValue($field, $originalTimelineValues[$field] ?? null),
                    'new' => $this->formatTaskTimelineValue($field, $task->getAttribute($field)),
                ];
            })
            ->values()
            ->all();
    }

    private function formatTaskTimelineValue(string $field, mixed $value): string
    {
        if ($field === 'estimated_time_seconds') {
            return $value === null
                ? '--'
                : formatMinutesToHoursMinutes((int) round((int) $value / 60));
        }

        return AppServiceProvider::formatAppDateTime($value);
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

    // Get restriction message and status if the task cannot be deleted, otherwise return null
    public function getDeleteRestriction(Task $task): ?array
    {
        if ($task->timeLogs()->exists()) {
            return [
                'message' => 'Sorry... Task logs exist.',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'reason' => 'running_timer_exists',
            ];
        }

        if ($task->childTasks()->exists()) {
            return [
                'message' => 'Delete the subtasks first before removing this task.',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'reason' => 'subtasks_exist',
            ];
        }

        return null;
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
            $runningLogQuery = TaskTimeLog::query()
                ->where('task_id', $task->id)
                ->where('is_running', 1);

            $log = null;

            if ($task->current_assignee_id) {
                $log = (clone $runningLogQuery)
                    ->where('user_id', $task->current_assignee_id)
                    ->latest('started_at')
                    ->first();
            }

            if (! $log) {
                $log = (clone $runningLogQuery)
                    ->where('user_id', $actor->id)
                    ->latest('started_at')
                    ->first();
            }

            if (! $log) {
                $log = $runningLogQuery
                    ->latest('started_at')
                    ->first();
            }

            if (! $log) {
                throw new \RuntimeException('Timer not found');
            }

            $stoppedAt = now();
            $duration = max(0, $log->started_at?->diffInSeconds($stoppedAt) ?? 0);

            // Update time log
            $log->update([
                'ended_at' => $stoppedAt,
                'duration_seconds' => $duration,
                'is_running' => 0,
            ]);

            // Update assignment log
            if ($log->task_assignment_log_id) {
                TaskAssignmentLog::where('id', $log->task_assignment_log_id)
                    ->increment('worked_time_seconds', $duration);
            }

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
            ->with([
                'task:id,name,code,current_assignee_id',
                'task.currentAssignee:id,name',
            ])
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
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['reason' => 'already_running']
            );
        }

        $runningTaskLabel = $runningTimer->task?->name
            ?: ($runningTimer->task?->code ?: ('task #' . $runningTimer->task_id));

        return $this->buildTaskStartRestriction(
            "Please stop the timer for '{$runningTaskLabel}' before starting another task.",
            Response::HTTP_UNPROCESSABLE_ENTITY,
            [
                'reason' => 'running_timer_exists',
                'running_task_id' => (int) $runningTimer->task_id,
                'running_task_name' => $runningTaskLabel,
                'running_task_assignee_name' => $runningTimer->task?->currentAssignee?->name,
            ]
        );
    }

    private function buildTaskStartRestriction(string $message, int $status, array $extra = []): array
    {
        return array_merge([
            'message' => $message,
            'status' => $status,
        ], $extra);
    }

    // Check whether current user can stop timer on this task
    public function isAllowedToStop(Task $task, ?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        if (! $user) {
            return false;
        }

        $task->load([
            'currentAssignee.details',
            'project.teamLeader',
            'projectMilestone:id,owner_id,name',
        ]);

        // if task is not assigned, no one can stop timer
        if ($task->current_assignee_id === null) {
            return false;
        }

        // Super admin
        if ($user->is_super_admin) {
            return true;
        }

        // Current assignee
        if ((int) $task->current_assignee_id === (int) $user->id) {
            return true;
        }

        /**
         * Allow if assignee is accessible by this user
         * Covers:
         * user.view_all_users permission
         * users added by this user
         * nested reporter hierarchy users
         * direct manager users
         */
        if (
            User::query()
            ->accessibleBy($user)
            ->whereKey($task->current_assignee_id)
            ->exists()
        ) {
            return true;
        }

        // Allow if project team leader
        if ((int) ($task->project?->teamLeader?->id ?? 0) === (int) $user->id) {
            return true;
        }

        // Allow if milestone owner
        if ((int) ($task->projectMilestone?->owner_id ?? 0) === (int) $user->id) {
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

    private function buildTaskTimeComparison(Task $task): array
    {
        $estimatedSeconds = max(0, (int) ($task->estimated_time_seconds ?? 0));
        $actualSeconds = max(0, (int) ($task->actual_time_seconds ?? 0));
        $hasEstimate = $estimatedSeconds > 0;
        $progressPercent = $hasEstimate ? (int) round(($actualSeconds / $estimatedSeconds) * 100) : 0;
        $barPercent = max(0, min(100, $progressPercent));
        $isOverEstimate = $hasEstimate && $actualSeconds > $estimatedSeconds;
        $differenceSeconds = $hasEstimate ? abs($estimatedSeconds - $actualSeconds) : 0;

        return [
            'estimated_seconds' => $estimatedSeconds,
            'actual_seconds' => $actualSeconds,
            'estimated_formatted' => $this->formatDuration($estimatedSeconds),
            'actual_formatted' => $this->formatDuration($actualSeconds),
            'remaining_or_over_formatted' => $hasEstimate ? $this->formatDuration($differenceSeconds) : '--',
            'remaining_or_over_label' => ! $hasEstimate ? 'No estimate' : ($isOverEstimate ? 'Over' : 'Remaining'),
            'progress_percent' => $progressPercent,
            'bar_percent' => $barPercent,
            'is_over_estimate' => $isOverEstimate,
            'has_estimate' => $hasEstimate,
            'status_label' => ! $hasEstimate ? 'No estimate' : ($isOverEstimate ? 'Over estimate' : 'Within estimate'),
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $normalizedSeconds = max(0, $seconds);
        $hours = intdiv($normalizedSeconds, 3600);
        $minutes = intdiv($normalizedSeconds % 3600, 60);

        return sprintf('%02dh %02dm', $hours, $minutes);
    }

    public function transitionStatus(User $user, int $movedTaskId, array $taskIds, int $statusId): array
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

            $timerStoppedPayload = null;

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
                    $timerStoppedPayload = $this->stopRunningTimersIfStatusInactive(
                        $task,
                        $previousStatusId,
                        $newStatus,
                        $user
                    ) ?: $timerStoppedPayload;

                    app(NotificationService::class)->notifyTaskStatusChanged(
                        $task,
                        $user,
                        $this->getStatusName($previousStatusId),
                        $newStatus->name
                    );
                }
            }

            return [
                'task' => Task::query()
                    ->with($this->relations())
                    ->findOrFail($movedTaskId),
                'timer_stopped' => $timerStoppedPayload,
            ];
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

    private function stopRunningTimersIfStatusInactive(Task $task, ?int $previousStatusId, ?TaskStatus $newStatus, ?User $actor = null): ?array
    {
        if (! $newStatus || $newStatus->type === 'active' || $previousStatusId === (int) $newStatus->id) {
            return null;
        }

        $timerStoppedPayload = $this->stopAllRunningTimers($task);

        if (! $timerStoppedPayload) {
            return null;
        }

        if ($actor) {
            $this->notificationService->notifyTaskTimerStoppedBecauseStatusChanged($task, $actor, $newStatus->name ?? 'the updated status');
        }

        return $timerStoppedPayload;
    }

    private function stopAllRunningTimers(Task $task): ?array
    {
        $runningLogs = TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->where('is_running', true)
            ->get();

        if ($runningLogs->isEmpty()) {
            return null;
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

        $nextTotalSeconds = (int) ($task->actual_time_seconds ?? 0) + $totalDuration;

        if ($totalDuration > 0) {
            $task->increment('actual_time_seconds', $totalDuration);
        }

        return [
            'task_id' => (int) $task->id,
            'total_seconds' => $nextTotalSeconds,
        ];
    }

    /** Task dropdown options */
    public function getTaskDropdownOptions($user, int $projectId, ?int $milestoneId = null, ?int $sprintId = null)
    {
        return Task::query()
            ->accessibleBy($user)
            ->where('project_id', $projectId)
            ->when($milestoneId, function ($query) use ($milestoneId) {
                $query->where('project_milestone_id', $milestoneId);
            })
            ->when($sprintId, function ($query) use ($sprintId) {
                $query->where('project_sprint_id', $sprintId);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                ];
            })
            ->values();
    }


}

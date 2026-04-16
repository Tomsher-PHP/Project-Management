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
    // Initialize task service dependencies
    public function __construct(
        protected ProjectServices $projectServices,
        protected TaskQueryService $queryService,
        protected TaskFilterService $filterService
    ) {}

    // Get paginated task list for the current user
    public function getList(User $user, array $filters, $perPage)
    {
        $query = $this->queryService->baseQuery($user);

        return $this->filterService
            ->sort(
                $this->filterService->apply($query, $filters),
                $filters
            )
            ->whereNull('parent_task_id')
            ->with($this->relations())
            ->withCount('childTasks')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Get kanban task groups by status for the current user
    public function getKanban(User $user, array $filters, string $flowType, $statuses)
    {
        $query = $this->queryService->baseQuery($user);

        $tasks = $this->filterService
            ->apply($query, $filters)
            ->whereHas('project', fn($q) => $q->where('project_flow', $flowType))
            ->with($this->relations())
            // ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('sort_order')
            ->get();

        // Group tasks
        $tasksByStatus = $tasks->groupBy('status_id');

        // Ensure all statuses exist (even empty)
        return collect($statuses)->mapWithKeys(function ($status) use ($tasksByStatus) {
            return [
                $status->id => $tasksByStatus[$status->id] ?? collect()
            ];
        });
    }

    // Define related models to eager load for tasks
    private function relations()
    {
        return [
            'project:id,name,project_code,project_flow',
            'projectModule:id,name',
            'projectSprint:id,name',
            'currentAssignee:id,name',
            'status:id,name,color',
            'taskType:id,name,code,color',
            'taskMode:id,name,code,color',
        ];
    }

    // Create a simple task with default placement and tags
    public function createQuickTask(Project $project, array $validated): Task
    {
        return DB::transaction(function () use ($project, $validated) {
            $defaults = $this->resolveDefaults($project);
            $placement = $this->finalizePlacement(
                $project,
                ! empty($validated['project_module_id']) ? (int) $validated['project_module_id'] : null,
                ! empty($validated['project_sprint_id']) ? (int) $validated['project_sprint_id'] : null
            );

            $payload = $this->buildCreatePayload(
                project: $project,
                validated: $validated,
                defaults: $defaults,
                placement: $placement
            );

            $task = $project->tasks()->create($payload);

            if (!empty($task->current_assignee_id)) {
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

            $placement = $this->finalizePlacement(
                $project,
                ! empty($validated['project_module_id']) ? (int) $validated['project_module_id'] : null,
                ! empty($validated['project_sprint_id']) ? (int) $validated['project_sprint_id'] : null
            );

            $payload = $this->buildUpdatePayload(
                task: $task,
                validated: $validated,
                placement: $placement
            );

            $task->update($payload);

            if (array_key_exists('tag_ids', $validated)) {
                $this->syncTags($task, $validated['tag_ids'] ?? []);
            }

            $newStatusId = ! empty($validated['status_id']) ? (int) $validated['status_id'] : null;
            $newAssigneeId = ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null;

            $this->recordStatusHistoryIfChanged($task, $previousStatusId, $newStatusId);
            $this->syncAssignmentIfChanged($task, $previousAssigneeId, $newAssigneeId);

            return $task->fresh();
        });
    }

    // Determine project module and sprint placement for a task
    public function finalizePlacement(Project $project, ?int $moduleId, ?int $sprintId): array
    {
        return $this->projectServices->finalizeTaskPlacement($project, $moduleId, $sprintId);
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
            'project_module_id' => $placement['project_module_id'],
            'project_sprint_id' => $resolvedSprintId,
            'parent_task_id' => ! empty($validated['parent_task_id']) ? (int) $validated['parent_task_id'] : null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status_id' => ! empty($validated['status_id']) ? (int) $validated['status_id'] : $defaults['status_id'],
            'task_type_id' => $validated['task_type_id'] ?? $defaults['task_type_id'],
            'task_mode_id' => $validated['task_mode_id'] ?? $defaults['task_mode_id'],
            'priority' => $validated['priority'] ?? $defaults['priority'],
            'current_assignee_id' => ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null,
            'due_date' => $validated['due_date'] ?? null,
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
            'project_module_id' => $placement['project_module_id'],
            'project_sprint_id' => $placement['project_sprint_id'],
            'parent_task_id' => ! empty($validated['parent_task_id']) ? (int) $validated['parent_task_id'] : null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status_id' => ! empty($validated['status_id']) ? (int) $validated['status_id'] : null,
            'task_type_id' => $validated['task_type_id'],
            'task_mode_id' => $validated['task_mode_id'],
            'priority' => $validated['priority'],
            'current_assignee_id' => ! empty($validated['current_assignee_id']) ? (int) $validated['current_assignee_id'] : null,
            'due_date' => $validated['due_date'] ?? null,
            'completed_at' => $validated['completed_at'] ?? null,
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

    // Start/Stop Timer related methods

    // Start a timer for the given task and user
    public function startTimer(int $taskId, int $userId)
    {
        return DB::transaction(function () use ($taskId, $userId) {

            // Check if already running
            $running = TaskTimeLog::where('task_id', $taskId)
                ->where('user_id', $userId)
                ->where('is_running', 1)
                ->first();

            if ($running) {
                throw new \Exception('Timer already running');
            }

            $assignment = TaskAssignmentLog::where('task_id', $taskId)
                ->where('user_id', $userId)
                ->where('is_current', 1)
                ->first();

            if (!$assignment) {
                throw new \Exception('Assignment not found');
            }

            return TaskTimeLog::create([
                'task_id' => $taskId,
                'user_id' => $userId,
                'task_assignment_log_id' => $assignment->id,
                'started_at' => now(),
                'is_running' => 1,
                'added_by' => $userId,
            ]);
        });
    }

    // Stop the currently running timer and record duration
    public function stopTimer(Task $task)
    {
        return DB::transaction(function () use ($task) {
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

            return $log;
        });
    }

    // Check whether current user can start timer on this task
    public function isAllowedToStart(Task $task): bool
    {
        $user = auth()->user();

        // if task is not assigned, no one can stop timer
        if ($task->current_assignee_id === null) {
            return false;
        }

        return $task->current_assignee_id === $user->id;
    }

    // Check whether current user can stop timer on this task
    public function isAllowedToStop(Task $task): bool
    {
        $user = auth()->user();

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

        return false;
    }

    public function isAllowedChangeStatus(Task $task, User $user): bool
    {
        return $this->isAllowedToStop($task);
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

    public function transitionStatus(User $user, int $movedTaskId, array $taskIds, int $statusId): void
    {
        DB::transaction(function () use ($user, $movedTaskId, $taskIds, $statusId) {

            $movedTask = Task::find($movedTaskId);

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

            // Bulk fetch
            $tasks = Task::whereIn('id', $taskIds)->get()->keyBy('id');

            foreach ($taskIds as $index => $taskId) {

                $task = $tasks[$taskId] ?? null;
                if (! $task) continue;

                $previousStatusId = $task->status_id;

                $task->update([
                    'status_id' => $statusId,
                    'sort_order' => $index
                ]);

                if ($task->id === $movedTask->id) {
                    $this->recordStatusHistoryIfChanged(
                        $task,
                        $previousStatusId,
                        $statusId
                    );
                }
            }
        });
    }
}

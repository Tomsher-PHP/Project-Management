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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskServices
{
    public function __construct(
        protected ProjectServices $projectServices
    ) {}

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

    public function finalizePlacement(Project $project, ?int $moduleId, ?int $sprintId): array
    {
        return $this->projectServices->finalizeTaskPlacement($project, $moduleId, $sprintId);
    }

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

    public function syncTags(Task $task, array $submittedTags): void
    {
        $task->tags()->sync($this->resolveTaskTagIds($submittedTags));
    }

    public function recordStatusHistoryIfChanged(Task $task, ?int $previousStatusId, ?int $newStatusId): void
    {
        if ($newStatusId && $newStatusId !== $previousStatusId) {
            TaskStatusHistory::create([
                'task_id' => $task->id,
                'status_id' => $newStatusId,
            ]);
        }
    }

    public function syncAssignmentIfChanged(Task $task, ?int $previousAssigneeId, ?int $newAssigneeId): void
    {
        if ($newAssigneeId !== $previousAssigneeId) {
            $this->syncTaskAssignmentState($task, $newAssigneeId);
        }
    }

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

    protected function getDefaultTaskPriorityValue(): string
    {
        $priorities = config('project_constants.task_priorities', []);

        if (array_key_exists('medium', $priorities)) {
            return 'medium';
        }

        return (string) (array_key_first($priorities) ?? 'medium');
    }

    protected function getDefaultTaskEstimateSeconds(Project $project): int
    {
        return max(0, (int) ($project->default_task_estimate_seconds ?? 0));
    }

    // Start/Stop Timer related methods
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

    // call this method to check if user can start timer for a task
    public function isAllowedToStart(Task $task): bool
    {
        $user = auth()->user();
        return $task->current_assignee_id === $user->id;
    }

    // call this method to check if user can stop timer for a task
    public function isAllowedToStop(Task $task): bool
    {
        $user = auth()->user();
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

    public function getTotalTrackedSeconds(int $taskId, int $userId): int
    {
        // 1. Sum completed durations
        $total = TaskTimeLog::where('task_id', $taskId)
            ->where('user_id', $userId)
            ->where('is_running', 0)
            ->sum('duration_seconds');

        return $total;

        // 2. Add running session (if exists)
        // $runningLog = TaskTimeLog::where('task_id', $taskId)
        //     ->where('user_id', $userId)
        //     ->where('is_running', 1)
        //     ->latest()
        //     ->first();

        // if ($runningLog) {
        //     $total += $runningLog->started_at->diffInSeconds(now());
        // }

        // return $total;
    }
}

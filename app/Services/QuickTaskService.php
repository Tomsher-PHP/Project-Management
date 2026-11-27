<?php

namespace App\Services;

use App\Exceptions\TaskTimerAlreadyRunningException;
use App\Exceptions\TaskTimerStateConflictException;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use App\Services\Task\RunningTaskNavbarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class QuickTaskService
{
    public function __construct(
        protected TaskServices $taskServices,
        protected NotificationService $notificationService,
        protected RunningTaskNavbarService $runningTaskNavbarService
    ) {}

    /**
     * Create a Quick Task with backend-enforced defaults and optionally start/switch its timer.
     *
     * @return array{
     *     task: Task,
     *     timer_started: bool,
     *     timer_result: array|null,
     *     navbar_timer: array|null
     * }
     */
    public function createQuickTask(User $user, int $projectId, string $name, bool $startTimer = false): array
    {
        $project = $this->resolveEligibleProject($user, $projectId);
        $defaults = $this->resolveRequiredDefaults($project, $startTimer);

        $result = DB::transaction(function () use ($user, $project, $name, $startTimer, $defaults) {
            $placement = $this->taskServices->finalizePlacement($project, null, null);
            $resolvedSprintId = $placement['project_sprint_id'];

            $payload = [
                'project_timeline_id' => $project->display_timeline?->id,
                'project_milestone_id' => $placement['project_milestone_id'],
                'project_sprint_id' => $resolvedSprintId,
                'parent_task_id' => null,
                'task_schedule_id' => null,
                'scheduled_for_date' => null,
                'name' => trim($name),
                'description' => null,
                'status_id' => $defaults['status_id'],
                'task_type_id' => $defaults['task_type_id'],
                'task_mode_id' => $defaults['task_mode_id'],
                'priority' => $defaults['priority'],
                'current_assignee_id' => (int) $user->id,
                'due_date_time' => null,
                'estimated_time_seconds' => 0,
                'is_billable' => false,
                'sort_order' => Task::nextSortOrder($project->id, $resolvedSprintId),
                'request_type' => Task::REQUEST_TYPE_ASSIGNED,
                'request_status' => Task::REQUEST_APPROVED,
                'approved_by' => (int) $user->id,
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ];

            $task = $project->tasks()->create($payload);

            $this->taskServices->recordStatusHistoryIfChanged($task, null, (int) $defaults['status_id']);
            $this->taskServices->syncTaskAssignmentState($task, (int) $user->id);

            $timerResult = null;
            if ($startTimer) {
                $activeStatusId = (int) $defaults['active_status_id'];
                if ((int) $task->status_id !== $activeStatusId) {
                    $previousStatusId = (int) $task->status_id;
                    $task->update(['status_id' => $activeStatusId]);
                    $this->taskServices->recordStatusHistoryIfChanged($task, $previousStatusId, $activeStatusId);
                    $task->refresh();
                }

                $timerResult = $this->taskServices->switchTimer($task, $user);
            }

            return [
                'task' => $task->fresh(),
                'timer_started' => $startTimer,
                'timer_result' => $timerResult,
            ];
        });

        $this->notificationService->sendTaskAssignmentIfNeeded(
            $result['task'],
            (int) $user->id
        );

        $result['navbar_timer'] = $startTimer
            ? $this->runningTaskNavbarService->getFrontendStateForUser((int) $user->id)
            : null;

        return $result;
    }

    /**
     * Resolve an eligible project for Quick Task creation.
     */
    public function resolveEligibleProject(User $user, int $projectId): Project
    {
        $systemProject = Project::query()
            ->whereKey($projectId)
            ->where('is_system', true)
            ->where('is_active', true)
            ->first();

        if ($systemProject) {
            return $systemProject;
        }

        $regularProject = Project::query()
            ->accessibleBy($user)
            ->whereKey($projectId)
            ->first();

        if (! $regularProject) {
            throw ValidationException::withMessages([
                'project_id' => 'You do not have access to the selected project.',
            ]);
        }

        $isActiveMember = $regularProject->activeMembers()
            ->whereKey($user->id)
            ->exists();

        if (! $isActiveMember) {
            throw ValidationException::withMessages([
                'project_id' => 'You must be an active member of the selected project to create a Quick Task.',
            ]);
        }

        return $regularProject;
    }

    /**
     * Resolve and strictly validate required Quick Task default configuration.
     *
     * @return array{
     *     status_id: int,
     *     active_status_id: int|null,
     *     task_type_id: int,
     *     task_mode_id: int,
     *     priority: string
     * }
     */
    protected function resolveRequiredDefaults(Project $project, bool $startTimer): array
    {
        $taskTypeId = TaskType::query()
            ->active()
            ->where('is_default', true)
            ->orderBy('id')
            ->value('id');

        if (! $taskTypeId) {
            throw new RuntimeException('Default Task Type is not configured. Please contact an administrator.');
        }

        $taskModeId = TaskMode::query()
            ->active()
            ->where('is_default', true)
            ->orderBy('id')
            ->value('id');

        if (! $taskModeId) {
            throw new RuntimeException('Default Task Mode is not configured. Please contact an administrator.');
        }

        $flowType = $project->project_flow;
        $statusId = TaskStatus::query()
            ->active()
            ->where('flow_type', $flowType)
            ->where('is_default', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        if (! $statusId) {
            throw new RuntimeException("Default Task Status for '{$flowType}' flow is not configured.");
        }

        $activeStatusId = null;
        if ($startTimer) {
            $activeStatusId = TaskStatus::query()
                ->active()
                ->where('flow_type', $flowType)
                ->where('type', 'active')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('id');

            if (! $activeStatusId) {
                throw new RuntimeException("Active Task Status for '{$flowType}' flow is not configured.");
            }
        }

        $priorities = config('project_constants.task_priorities', []);
        $priority = array_key_exists('medium', $priorities)
            ? 'medium'
            : (string) (array_key_first($priorities) ?? 'medium');

        return [
            'status_id' => (int) $statusId,
            'active_status_id' => $activeStatusId ? (int) $activeStatusId : null,
            'task_type_id' => (int) $taskTypeId,
            'task_mode_id' => (int) $taskModeId,
            'priority' => $priority,
        ];
    }
}

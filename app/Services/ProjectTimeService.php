<?php

namespace App\Services;

use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Models\TaskTimeLog;
use Illuminate\Support\Facades\DB;

class ProjectTimeService
{
    public function recalculateTaskActual(?int $taskId): void
    {
        $taskId = $this->normalizeId($taskId);

        if ($taskId === null) {
            return;
        }

        $task = Task::query()->find($taskId);

        if (! $task) {
            return;
        }

        $actualSeconds = class_exists(TaskTimeLog::class)
            ? (int) TaskTimeLog::query()
                ->where('task_id', $task->id)
                ->where('is_approved', true)
                ->sum('duration_seconds')
            : 0;

        $task->updateQuietly([
            'actual_time_seconds' => $actualSeconds,
        ]);
    }

    public function recalculateTaskDerived(?int $taskId): void
    {
        $taskId = $this->normalizeId($taskId);

        if ($taskId === null) {
            return;
        }

        $task = Task::query()->find($taskId);

        if (! $task) {
            return;
        }

        $derivedSeconds = (int) Task::query()
            ->where('parent_task_id', $task->id)
            ->where('request_status', Task::REQUEST_APPROVED)
            ->sum('estimated_time_seconds');

        $task->updateQuietly([
            'derived_time_seconds' => $derivedSeconds,
        ]);
    }

    public function recalculateSprintTimes(?int $sprintId): void
    {
        $sprintId = $this->normalizeId($sprintId);

        if ($sprintId === null) {
            return;
        }

        $projectSprint = ProjectSprint::query()->find($sprintId);

        if (! $projectSprint) {
            return;
        }

        $approvedTasks = Task::query()
            ->where('project_sprint_id', $projectSprint->id)
            ->where('request_status', Task::REQUEST_APPROVED);

        $projectSprint->updateQuietly([
            'derived_time_seconds' => (int) (clone $approvedTasks)->sum('estimated_time_seconds'),
            'actual_time_seconds' => (int) (clone $approvedTasks)->sum('actual_time_seconds'),
        ]);
    }

    public function recalculateMilestoneTimes(?int $milestoneId): void
    {
        $milestoneId = $this->normalizeId($milestoneId);

        if ($milestoneId === null) {
            return;
        }

        $projectMilestone = ProjectMilestone::query()->find($milestoneId);

        if (! $projectMilestone) {
            return;
        }

        $projectSprints = ProjectSprint::query()
            ->where('project_milestone_id', $projectMilestone->id);

        $projectMilestone->updateQuietly([
            'derived_time_seconds' => (int) (clone $projectSprints)->sum('estimated_time_seconds'),
            'actual_time_seconds' => (int) (clone $projectSprints)->sum('actual_time_seconds'),
        ]);
    }

    public function recalculateByTask(?int $taskId): void
    {
        $taskId = $this->normalizeId($taskId);

        if ($taskId === null) {
            return;
        }

        DB::transaction(function () use ($taskId) {
            $task = Task::withTrashed()->find($taskId);

            if (! $task) {
                return;
            }

            $this->recalculateTaskActual($taskId);
            $this->recalculateTaskDerived($taskId);

            $parentTaskId = $this->normalizeId($task->parent_task_id);

            if ($parentTaskId !== null) {
                $this->recalculateTaskDerived($parentTaskId);
            }

            $sprintId = $this->normalizeId($task->project_sprint_id);

            if ($sprintId !== null) {
                $this->recalculateSprintTimes($sprintId);
                $this->recalculateMilestoneTimes($this->findSprintMilestoneId($sprintId));

                return;
            }

            $milestoneId = $this->normalizeId($task->project_milestone_id);

            if ($milestoneId !== null) {
                $this->recalculateMilestoneTimes($milestoneId);
            }
        });
    }

    public function recalculateOldTaskRelations(array $original): void
    {
        DB::transaction(function () use ($original) {
            $parentTaskId = $this->extractId($original, 'parent_task_id');

            if ($parentTaskId !== null) {
                $this->recalculateTaskDerived($parentTaskId);
            }

            $oldSprintId = $this->extractId($original, 'project_sprint_id');

            if ($oldSprintId !== null) {
                $this->recalculateSprintTimes($oldSprintId);
            }

            foreach ($this->resolveOldTaskMilestoneIds($original, $oldSprintId) as $milestoneId) {
                $this->recalculateMilestoneTimes($milestoneId);
            }
        });
    }

    public function recalculateBySprint(?int $sprintId): void
    {
        $sprintId = $this->normalizeId($sprintId);

        if ($sprintId === null) {
            return;
        }

        DB::transaction(function () use ($sprintId) {
            $this->recalculateSprintTimes($sprintId);
            $this->recalculateMilestoneTimes($this->findSprintMilestoneId($sprintId));
        });
    }

    public function recalculateOldSprintRelations(array $original): void
    {
        DB::transaction(function () use ($original) {
            $milestoneId = $this->extractId($original, 'project_milestone_id');

            if ($milestoneId !== null) {
                $this->recalculateMilestoneTimes($milestoneId);
            }
        });
    }

    public function recalculateProject(int $projectId): void
    {
        $projectId = $this->normalizeId($projectId);

        if ($projectId === null) {
            return;
        }

        DB::transaction(function () use ($projectId) {
            $taskIds = Task::query()
                ->where('project_id', $projectId)
                ->pluck('id');

            foreach ($taskIds as $taskId) {
                $taskId = (int) $taskId;
                $this->recalculateTaskActual($taskId);
                $this->recalculateTaskDerived($taskId);
            }

            $sprintIds = ProjectSprint::query()
                ->where('project_id', $projectId)
                ->pluck('id');

            foreach ($sprintIds as $sprintId) {
                $this->recalculateSprintTimes((int) $sprintId);
            }

            $milestoneIds = ProjectMilestone::query()
                ->where('project_id', $projectId)
                ->pluck('id');

            foreach ($milestoneIds as $milestoneId) {
                $this->recalculateMilestoneTimes((int) $milestoneId);
            }
        });
    }

    private function resolveOldTaskMilestoneIds(array $original, ?int $oldSprintId): array
    {
        $milestoneIds = [];

        if ($oldSprintId !== null) {
            $milestoneIds[] = $this->findSprintMilestoneId($oldSprintId);
        }

        $milestoneIds[] = $this->extractId($original, 'project_milestone_id');

        return collect($milestoneIds)
            ->filter(fn($id) => $id !== null)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function findSprintMilestoneId(?int $sprintId): ?int
    {
        $sprintId = $this->normalizeId($sprintId);

        if ($sprintId === null) {
            return null;
        }

        $projectSprint = ProjectSprint::withTrashed()->find($sprintId);

        return $this->normalizeId($projectSprint?->project_milestone_id);
    }

    private function extractId(array $values, string $key): ?int
    {
        return $this->normalizeId($values[$key] ?? null);
    }

    private function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}

<?php

namespace App\Services;

use App\Models\AgileMilestoneStatus;
use App\Models\AgileSprintStatus;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Models\TaskStatus;

class TaskStatusSyncService
{
    public function syncAgileParentStatus(Task $task): void
    {
        $task = $task->fresh([
            'project',
        ]);

        if (! $task || ! $task->project) {
            return;
        }

        if (! $task->project->is_agile) {
            return;
        }

        if (! $task->project_sprint_id && ! $task->project_milestone_id) {
            return;
        }

        $this->syncAgileParentStatusByRelations(
            $task->project_sprint_id ? (int) $task->project_sprint_id : null,
            $task->project_milestone_id ? (int) $task->project_milestone_id : null
        );
    }

    public function syncAgileParentStatusByRelations(?int $sprintId = null, ?int $milestoneId = null, bool $forceInProgress = false): void
    {
        if ($sprintId) {
            $this->syncSprintStatus($sprintId, $forceInProgress);
        }

        if ($milestoneId) {
            $this->syncMilestoneStatus($milestoneId, $forceInProgress);
        }
    }

    private function syncSprintStatus(int $sprintId, bool $forceInProgress): void
    {
        $sprint = ProjectSprint::query()
            ->with('project:id,project_flow')
            ->find($sprintId);

        if (! $sprint || ! $sprint->project?->is_agile) {
            return;
        }

        if ($forceInProgress || $this->hasSprintNonCompletedTasks($sprintId)) {
            $this->updateSprintStatus($sprint, $this->getSprintInProgressStatusId());
            return;
        }

        if ($this->areAllSprintTasksCompleted($sprintId)) {
            $this->updateSprintStatus($sprint, $this->getSprintCompletedStatusId());
        }
    }

    private function syncMilestoneStatus(int $milestoneId, bool $forceInProgress): void
    {
        $milestone = ProjectMilestone::query()
            ->with('project:id,project_flow')
            ->find($milestoneId);

        if (! $milestone || ! $milestone->project?->is_agile) {
            return;
        }

        if ($forceInProgress || $this->hasMilestoneNonCompletedTasks($milestoneId)) {
            $this->updateMilestoneStatus($milestone, $this->getMilestoneInProgressStatusId());
            return;
        }

        if ($this->areAllMilestoneTasksCompleted($milestoneId)) {
            $this->updateMilestoneStatus($milestone, $this->getMilestoneCompletedStatusId());
        }
    }

    private function hasSprintNonCompletedTasks(int $sprintId): bool
    {
        return $this->buildEligibleSprintTasksQuery($sprintId)
            ->where(function ($query) {
                $query
                    ->whereNull('status_id')
                    ->orWhereDoesntHave('status', function ($statusQuery) {
                        $statusQuery->where(function ($completedQuery) {
                            $completedQuery
                                ->where('is_completed', true)
                                ->orWhere('type', TaskStatus::TYPE_COMPLETED);
                        });
                    });
            })
            ->exists();
    }

    private function hasMilestoneNonCompletedTasks(int $milestoneId): bool
    {
        return $this->buildEligibleMilestoneTasksQuery($milestoneId)
            ->where(function ($query) {
                $query
                    ->whereNull('status_id')
                    ->orWhereDoesntHave('status', function ($statusQuery) {
                        $statusQuery->where(function ($completedQuery) {
                            $completedQuery
                                ->where('is_completed', true)
                                ->orWhere('type', TaskStatus::TYPE_COMPLETED);
                        });
                    });
            })
            ->exists();
    }

    private function areAllSprintTasksCompleted(int $sprintId): bool
    {
        $query = $this->buildEligibleSprintTasksQuery($sprintId);

        if (! $query->exists()) {
            return false;
        }

        return ! $query
            ->where(function ($statusQuery) {
                $statusQuery
                    ->whereNull('status_id')
                    ->orWhereDoesntHave('status', function ($taskStatusQuery) {
                        $taskStatusQuery->where(function ($completedQuery) {
                            $completedQuery
                                ->where('is_completed', true)
                                ->orWhere('type', TaskStatus::TYPE_COMPLETED);
                        });
                    });
            })
            ->exists();
    }

    private function areAllMilestoneTasksCompleted(int $milestoneId): bool
    {
        $query = $this->buildEligibleMilestoneTasksQuery($milestoneId);

        if (! $query->exists()) {
            return false;
        }

        return ! $query
            ->where(function ($statusQuery) {
                $statusQuery
                    ->whereNull('status_id')
                    ->orWhereDoesntHave('status', function ($taskStatusQuery) {
                        $taskStatusQuery->where(function ($completedQuery) {
                            $completedQuery
                                ->where('is_completed', true)
                                ->orWhere('type', TaskStatus::TYPE_COMPLETED);
                        });
                    });
            })
            ->exists();
    }

    private function buildEligibleSprintTasksQuery(int $sprintId)
    {
        return Task::query()
            ->where('project_sprint_id', $sprintId)
            ->where(function ($query) {
                $query
                    ->whereNull('request_status')
                    ->orWhere('request_status', '!=', Task::REQUEST_REJECTED);
            });
    }

    private function buildEligibleMilestoneTasksQuery(int $milestoneId)
    {
        return Task::query()
            ->where('project_milestone_id', $milestoneId)
            ->where(function ($query) {
                $query
                    ->whereNull('request_status')
                    ->orWhere('request_status', '!=', Task::REQUEST_REJECTED);
            });
    }

    private function updateSprintStatus(ProjectSprint $sprint, ?int $statusId): void
    {
        if (! $statusId || (int) $sprint->status_id === $statusId) {
            return;
        }

        $sprint->updateQuietly([
            'status_id' => $statusId,
        ]);
    }

    private function updateMilestoneStatus(ProjectMilestone $milestone, ?int $statusId): void
    {
        if (! $statusId || (int) $milestone->status_id === $statusId) {
            return;
        }

        $milestone->updateQuietly([
            'status_id' => $statusId,
        ]);
    }

    private function getSprintInProgressStatusId(): ?int
    {
        return AgileSprintStatus::query()
            ->active()
            ->where('code', 'in_progress')
            ->value('id');
    }

    private function getMilestoneInProgressStatusId(): ?int
    {
        return AgileMilestoneStatus::query()
            ->active()
            ->where('code', 'in_progress')
            ->value('id');
    }

    private function getSprintCompletedStatusId(): ?int
    {
        return AgileSprintStatus::query()
            ->active()
            ->where('is_completed', true)
            ->value('id');
    }

    private function getMilestoneCompletedStatusId(): ?int
    {
        return AgileMilestoneStatus::query()
            ->active()
            ->where('is_completed', true)
            ->value('id');
    }
}

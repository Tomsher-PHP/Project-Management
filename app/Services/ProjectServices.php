<?php

namespace App\Services;

use App\Models\AgileMilestoneStatus;
use App\Models\AgileSprintStatus;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectNote;
use App\Models\ProjectSprint;
use App\Models\ProjectStage;
use App\Models\ProjectStageHistory;
use App\Models\ProjectStatus;
use App\Models\ProjectStatusHistory;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProjectServices
{
    private const BACKLOG_MILESTONE_NAME = 'Unplanned Work';
    private const BACKLOG_SPRINT_NAME = 'Backlog';
    private const BACKLOG_MILESTONE_DESCRIPTION = 'Contains unplanned tasks waiting to be organized into the proper work area.';
    private const BACKLOG_SPRINT_DESCRIPTION = 'Contains pending tasks waiting to be scheduled into an active sprint.';

    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['project_flow'] = $data['project_flow'] ?? 'agile';
            $data['priority'] = $data['priority'] ?? 'medium';

            // Handle default start date
            $startDate = $data['start_date'] ?? now(config('constants.timezone'))->toDateString();

            // Handle end date logic
            if (empty($data['end_date'])) {
                $data['end_date'] = Carbon::parse($startDate)->addDays(7)->toDateString();
            }

            // Fresh projects should fall back to the configured default status.
            $data['status_id'] = $data['project_status']
                ?? ProjectStatus::active()->where('is_default', true)->value('id');
            unset($data['project_status']);

            $defaultProjectStageId = ProjectStage::active()->where('is_default', true)->value('id');

            $customer = Customer::find($data['customer_id']);

            // Create project
            $project = Project::create([
                'project_code' => Project::generateProjectCode(),
                'name' => $data['name'],
                'customer_id' => $data['customer_id'],
                'project_flow' => $data['project_flow'],
                'priority' => $data['priority'],
                'status_id' => $data['status_id'],
                'project_stage_id' => $defaultProjectStageId,
                'start_date' => $startDate,
                'end_date' => $data['end_date'],
                'sales_person_id' => $customer ? $customer->sales_person_id : null,
            ]);

            // Insert status history
            ProjectStatusHistory::create([
                'project_id' => $project->id,
                'status_id' => $project->status_id,
            ]);

            return $project;
        });
    }

    public function update(Project $project, array $data)
    {
        return DB::transaction(function () use ($project, $data) {
            // Convert minutes -> seconds
            if (array_key_exists('estimated_time_minutes', $data)) {
                $data['estimated_time_seconds'] = $data['estimated_time_minutes'] !== null
                    ? (int) $data['estimated_time_minutes'] * 60
                    : null;
                unset($data['estimated_time_minutes']);
            }

            if (array_key_exists('default_task_estimate_minutes', $data)) {
                $data['default_task_estimate_seconds'] = $data['default_task_estimate_minutes'] !== null
                    ? (int) $data['default_task_estimate_minutes'] * 60
                    : null;
                unset($data['default_task_estimate_minutes']);
            }

            // Default values
            $data['customer_id'] = $data['customer_id'] ?? null;
            $data['default_billable'] = $data['default_billable'] ?? 0;

            // Update project
            $project->update([
                'parent_project_id' => $data['parent_project_id'] ?? null,
                'name' => $data['name'],
                'customer_id' => $data['customer_id'],
                'priority' => $data['priority'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'customer_end_date' => $data['customer_end_date'] ?? null,
                'estimated_time_seconds' => $data['estimated_time_seconds'] ?? null,
                'default_task_estimate_seconds' => $data['default_task_estimate_seconds'] ?? null,
                'domain' => $data['domain'] ?? null,
                'sales_person_id' => $data['sales_person_id'] ?? null,
                'project_category_id' => $data['project_category_id'] ?? null,
                'default_billable' => $data['default_billable'],
            ]);

            // Attach project technologies
            if (isset($data['project_technology_ids'])) {
                $project->technologies()->sync($data['project_technology_ids']);
            }

            return $project->fresh(); // return updated model
        });
    }

    public function updateStatus(Project $project, int $statusId, ?string $statusDate = null, ?string $remarks = null): Project
    {
        return DB::transaction(function () use ($project, $statusId, $statusDate, $remarks) {
            if ((int) $project->status_id !== $statusId) {
                $fromStatusId = $project->status_id;

                $project->update([
                    'status_id' => $statusId,
                ]);

                $historyAddedAt = $this->resolveHistoryAddedAt($statusDate);

                ProjectStatusHistory::create([
                    'project_id' => $project->id,
                    'from_status_id' => $fromStatusId,
                    'status_id' => $statusId,
                    'added_at' => $historyAddedAt,
                    'remarks' => blank($remarks) ? null : $remarks,
                ]);
            }

            return $project->fresh();
        });
    }

    public function updateStage(Project $project, ?int $projectStageId, ?string $changeDate = null, ?string $remarks = null): Project
    {
        return DB::transaction(function () use ($project, $projectStageId, $changeDate, $remarks) {
            if ((int) ($project->project_stage_id ?? 0) !== (int) ($projectStageId ?? 0)) {
                $fromStageId = $project->project_stage_id;

                $project->update([
                    'project_stage_id' => $projectStageId,
                ]);

                $historyAddedAt = $this->resolveHistoryAddedAt($changeDate);

                ProjectStageHistory::create([
                    'project_id' => $project->id,
                    'from_stage_id' => $fromStageId,
                    'stage_id' => $projectStageId,
                    'added_at' => $historyAddedAt,
                    'remarks' => blank($remarks) ? null : $remarks,
                ]);
            }

            return $project->fresh();
        });
    }

    public function getTimelines(Project $project): array
    {
        return [
            'projectTimeline' => $this->buildTimeline($project->start_date, $project->end_date),
            'customerTimeline' => $this->buildTimeline($project->start_date, $project->customer_end_date),
        ];
    }

    public function uploadFile(Project $project, array $data, $category = null)
    {
        return DB::transaction(function () use ($project, $data, $category) {
            $attachments = [];
            if (!empty($data['project_files'])) {
                $directory = 'project_files/' . $project->project_code;

                foreach ($data['project_files'] as $file) {
                    $attachments[] = $this->attachmentService->upload(
                        $file,
                        $directory,
                        $project,
                        'public',
                        'public',
                        true,
                        $category
                    );
                }
            }

            return $attachments;
        });
    }

    public function createNote(Project $project, array $data): ProjectNote
    {
        return DB::transaction(function () use ($project, $data) {
            $note = $project->projectNotes()->create([
                'description' => $data['description'],
                'is_active' => true,
            ]);

            if (!empty($data['attachments'])) {
                $directory = 'project_files/' . $project->project_code . '/notes';

                foreach ($data['attachments'] as $file) {
                    $this->attachmentService->upload(
                        $file,
                        $directory,
                        $note,
                        'public',
                        'public',
                        false,
                        'project_note'
                    );
                }
            }

            return $note->load(['attachments', 'addedBy']);
        });
    }

    public function findOrCreateUnplannedWorkMilestone(Project $project): ProjectMilestone
    {
        $this->ensureAgileProject($project);

        return DB::transaction(function () use ($project) {
            Project::query()
                ->whereKey($project->id)
                ->lockForUpdate()
                ->first();

            $projectMilestone = ProjectMilestone::query()
                ->where('project_id', $project->id)
                ->where('is_backlog', true)
                ->orderBy('id')
                ->first();

            if (! $projectMilestone) {
                $projectMilestone = ProjectMilestone::onlyTrashed()
                    ->where('project_id', $project->id)
                    ->where('is_backlog', true)
                    ->orderBy('id')
                    ->first();
            }

            if (! $projectMilestone) {
                return $project->projectMilestones()->create([
                    'name' => self::BACKLOG_MILESTONE_NAME,
                    'description' => self::BACKLOG_MILESTONE_DESCRIPTION,
                    'status_id' => $this->defaultAgileMilestoneStatusId(),
                    'sort_order' => $this->nextProjectMilestoneOrder($project),
                    'is_backlog' => true,
                    'is_system' => true,
                ]);
            }

            if ($projectMilestone->trashed()) {
                $projectMilestone->restore();
                $projectMilestone->sort_order = $this->nextProjectMilestoneOrder($project);
            }

            $projectMilestone->fill([
                'name' => self::BACKLOG_MILESTONE_NAME,
                'description' => self::BACKLOG_MILESTONE_DESCRIPTION,
                'is_backlog' => true,
                'is_system' => true,
            ]);

            if (! $projectMilestone->status_id) {
                $projectMilestone->status_id = $this->defaultAgileMilestoneStatusId();
            }

            if ($projectMilestone->isDirty()) {
                $projectMilestone->save();
            }

            return $projectMilestone->fresh();
        });
    }

    public function findOrCreateBacklogSprint(Project $project, ProjectMilestone $milestone): ProjectSprint
    {
        $this->ensureAgileProject($project);
        $this->ensureProjectMilestoneBelongsToProject($project, $milestone);

        if (! $milestone->is_backlog) {
            throw new InvalidArgumentException('Backlog sprint must belong to a backlog project milestone.');
        }

        return DB::transaction(function () use ($project, $milestone) {
            Project::query()
                ->whereKey($project->id)
                ->lockForUpdate()
                ->first();

            $projectSprint = ProjectSprint::query()
                ->where('project_id', $project->id)
                ->where('is_backlog', true)
                ->orderBy('id')
                ->first();

            if (! $projectSprint) {
                $projectSprint = ProjectSprint::onlyTrashed()
                    ->where('project_id', $project->id)
                    ->where('is_backlog', true)
                    ->orderBy('id')
                    ->first();
            }

            if (! $projectSprint) {
                return $milestone->projectSprints()->create([
                    'project_id' => $project->id,
                    'name' => self::BACKLOG_SPRINT_NAME,
                    'description' => self::BACKLOG_SPRINT_DESCRIPTION,
                    'status_id' => $this->defaultAgileSprintStatusId(),
                    'sort_order' => $this->nextProjectSprintOrder($milestone),
                    'is_backlog' => true,
                    'is_system' => true,
                ]);
            }

            if ($projectSprint->trashed()) {
                $projectSprint->restore();
                $projectSprint->sort_order = $this->nextProjectSprintOrder($milestone);
            }

            $projectSprint->fill([
                'project_id' => $project->id,
                'project_milestone_id' => $milestone->id,
                'name' => self::BACKLOG_SPRINT_NAME,
                'description' => self::BACKLOG_SPRINT_DESCRIPTION,
                'is_backlog' => true,
                'is_system' => true,
            ]);

            if (! $projectSprint->status_id) {
                $projectSprint->status_id = $this->defaultAgileSprintStatusId();
            }

            if ($projectSprint->isDirty()) {
                $projectSprint->save();
            }

            return $projectSprint->fresh();
        });
    }

    public function finalizeTaskPlacement(Project $project, ?int $projectMilestoneId = null, ?int $projectSprintId = null): array
    {
        if ($project->is_linear) {
            return [
                'project_milestone' => null,
                'project_sprint' => null,
                'project_milestone_id' => null,
                'project_sprint_id' => null,
            ];
        }

        if ($projectSprintId) {
            $projectSprint = ProjectSprint::query()
                ->where('project_id', $project->id)
                ->find($projectSprintId);

            if (! $projectSprint) {
                throw new InvalidArgumentException('The selected sprint is invalid.');
            }

            $projectMilestone = ProjectMilestone::query()
                ->where('project_id', $project->id)
                ->find($projectSprint->project_milestone_id);

            return [
                'project_milestone' => $projectMilestone,
                'project_sprint' => $projectSprint,
                'project_milestone_id' => $projectSprint->project_milestone_id,
                'project_sprint_id' => $projectSprint->id,
            ];
        }

        $projectMilestone = $this->findOrCreateUnplannedWorkMilestone($project);
        $projectSprint = $this->findOrCreateBacklogSprint($project, $projectMilestone);

        return [
            'project_milestone' => $projectMilestone,
            'project_sprint' => $projectSprint,
            'project_milestone_id' => $projectMilestone->id,
            'project_sprint_id' => $projectSprint->id,
        ];
    }

    public function moveTaskToSprint(Project $project, Task $task, ?int $projectSprintId = null): Task
    {
        if ((int) $task->project_id !== (int) $project->id) {
            throw new InvalidArgumentException('The provided task does not belong to the given project.');
        }

        if (! $projectSprintId) {
            throw new InvalidArgumentException('A target sprint is required to move this task.');
        }

        $projectSprint = ProjectSprint::query()
            ->where('project_id', $project->id)
            ->find($projectSprintId);

        if (! $projectSprint) {
            throw new InvalidArgumentException('The selected sprint is invalid.');
        }

        if ($task->parent_task_id !== null) {
            throw new InvalidArgumentException('Subtasks cannot be moved to another sprint.');
        }

        if ((int) ($task->project_sprint_id ?? 0) === (int) $projectSprint->id) {
            throw new InvalidArgumentException('Please choose a different sprint.');
        }

        DB::transaction(function () use ($project, $task, $projectSprint) {
            $task->update([
                'project_sprint_id' => $projectSprint->id,
                'project_milestone_id' => $projectSprint->project_milestone_id,
                'sort_order' => Task::nextSortOrder($project->id, (int) $projectSprint->id),
            ]);

            $this->syncTaskDescendantPlacement(
                $task,
                (int) $projectSprint->project_milestone_id,
                (int) $projectSprint->id
            );
        });

        return $task->fresh();
    }

    public function syncTaskPlacementToDescendants(Task $task): void
    {
        $this->syncTaskDescendantPlacement(
            $task,
            $task->project_milestone_id ? (int) $task->project_milestone_id : null,
            $task->project_sprint_id ? (int) $task->project_sprint_id : null
        );
    }

    private function syncTaskDescendantPlacement(Task $task, ?int $projectMilestoneId, ?int $projectSprintId): void
    {
        $task->childTasks()
            ->get()
            ->each(function (Task $childTask) use ($projectMilestoneId, $projectSprintId) {
                $childTask->update([
                    'project_milestone_id' => $projectMilestoneId,
                    'project_sprint_id' => $projectSprintId,
                ]);

                $this->syncTaskDescendantPlacement($childTask, $projectMilestoneId, $projectSprintId);
            });
    }

    private function buildTimeline($startDate, $targetDate): array
    {
        $dateFormat = config('constants.date_format');
        $today = now(config('constants.timezone'))->startOfDay();

        if (! $startDate || ! $targetDate) {
            return [
                'percentage' => 0,
                'bar_class' => 'bg-gray-300',
                'text_class' => 'text-bgray-700 dark:text-bgray-300',
                'start_label' => $startDate?->format($dateFormat) ?? '--',
                'end_label' => $targetDate?->format($dateFormat) ?? '--',
            ];
        }

        $start = $startDate->copy()->startOfDay();
        $target = $targetDate->copy()->startOfDay();

        if ($target->lessThanOrEqualTo($start)) {
            return [
                'percentage' => 100,
                'bar_class' => 'bg-red-500',
                'text_class' => 'text-red-500',
                'start_label' => $start->format($dateFormat),
                'end_label' => $target->format($dateFormat),
            ];
        }

        $totalDays = max($start->diffInDays($target), 1);

        if ($today->lessThan($start)) {
            $percentage = 0;
        } elseif ($today->greaterThanOrEqualTo($target)) {
            $percentage = 100;
        } else {
            $percentage = (int) round(($start->diffInDays($today) / $totalDays) * 100);
        }

        if ($percentage <= 25) {
            $barClass = 'bg-success-300';
            $textClass = 'text-success-400';
        } elseif ($percentage <= 50) {
            $barClass = 'bg-yellow-400';
            $textClass = 'text-yellow-500';
        } elseif ($percentage <= 75) {
            $barClass = 'bg-orange-400';
            $textClass = 'text-orange-500';
        } else {
            $barClass = 'bg-red-500';
            $textClass = 'text-red-500';
        }

        return [
            'percentage' => $percentage,
            'bar_class' => $barClass,
            'text_class' => $textClass,
            'start_label' => $start->format($dateFormat),
            'end_label' => $target->format($dateFormat),
        ];
    }

    private function resolveHistoryAddedAt(?string $date): ?Carbon
    {
        if (blank($date)) {
            return null;
        }

        return Carbon::parse($date)->setTimeFrom(now());
    }

    private function defaultAgileMilestoneStatusId(): ?int
    {
        return AgileMilestoneStatus::query()
            ->where('is_default', true)
            ->value('id');
    }

    private function defaultAgileSprintStatusId(): ?int
    {
        return AgileSprintStatus::query()
            ->where('is_default', true)
            ->value('id');
    }

    private function nextProjectMilestoneOrder(Project $project): int
    {
        return ((int) $project->projectMilestones()->max('sort_order')) + 1;
    }

    private function nextProjectSprintOrder(ProjectMilestone $milestone): int
    {
        return ((int) $milestone->projectSprints()->max('sort_order')) + 1;
    }

    private function ensureAgileProject(Project $project): void
    {
        if (! $project->is_agile) {
            throw new InvalidArgumentException('Backlog records are only available for agile projects.');
        }
    }

    private function ensureProjectMilestoneBelongsToProject(Project $project, ProjectMilestone $milestone): void
    {
        if ((int) $milestone->project_id !== (int) $project->id) {
            throw new InvalidArgumentException('The provided project milestone does not belong to the given project.');
        }
    }

    public function getLatestProjectStatusChangeDate(Project $project): ?Carbon
    {
        $latestDate = $project->statusHistories()
            ->reorderDesc('added_at')
            ->orderByDesc('id')
            ->value('added_at');

        return $this->convertStoredTimestampToConfigTimezone($latestDate)?->startOfDay();
    }

    public function getLatestProjectStageChangeDate(Project $project): ?Carbon
    {
        $latestDate = $project->stageHistories()
            ->reorderDesc('added_at')
            ->orderByDesc('id')
            ->value('added_at');

        return $this->convertStoredTimestampToConfigTimezone($latestDate)?->startOfDay();
    }

    public function convertStoredTimestampToConfigTimezone(string|Carbon|null $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value, 'UTC')->timezone(config('constants.timezone'));
        } catch (\Throwable) {
            try {
                return Carbon::parse($value, config('constants.timezone'))->timezone(config('constants.timezone'));
            } catch (\Throwable) {
                return null;
            }
        }
    }

    public function getDeleteSummary(Project $project): array
    {
        $taskIdsQuery = $project->tasks()->select('id');

        return [
            'is_agile' => $project->is_agile,
            'milestones_count' => $project->projectMilestones()->count(),
            'sprints_count' => $project->projectSprints()->count(),
            'tasks_count' => $project->tasks()->count(),
            'sub_tasks_count' => $project->tasks()->whereNotNull('parent_task_id')->count(),
            'active_tasks_count' => $project->tasks()->whereHas('status', function ($query) {
                $query->where('type', TaskStatus::TYPE_ACTIVE);
            })->count(),
            'running_timers_count' => TaskTimeLog::whereIn('task_id', $taskIdsQuery)->whereNull('ended_at')->count(),
            'pending_requests_count' => $project->tasks()->where('request_status', 'pending')->count(),
            'scope_files_count' => $project->attachments()->where('category', 'scope_files')->count(),
        ];
    }
}

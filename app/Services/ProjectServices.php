<?php

namespace App\Services;

use App\Models\AgileMilestoneStatus;
use App\Models\AgileSprintStatus;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectNote;
use App\Models\ProjectNotificationDefault;
use App\Models\ProjectNotificationPreference;
use App\Models\ProjectSprint;
use App\Models\ProjectStage;
use App\Models\ProjectStageHistory;
use App\Models\ProjectStatus;
use App\Models\ProjectStatusHistory;
use App\Models\ProjectTimeline;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskStatus;
use App\Models\TaskStatusHistory;
use App\Models\TaskTimeLog;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProjectServices
{
    protected string $filesystemDisk;

    private const BACKLOG_MILESTONE_NAME = 'Unplanned Work';
    private const BACKLOG_SPRINT_NAME = 'Backlog';
    private const BACKLOG_MILESTONE_DESCRIPTION = 'Contains unplanned tasks waiting to be organized into the proper work area.';
    private const BACKLOG_SPRINT_DESCRIPTION = 'Contains pending tasks waiting to be scheduled into an active sprint.';
    private const PROJECT_TIMELINE_FIELDS = [
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'customer_end_date' => 'Customer End Date',
        'estimated_time_seconds' => 'Estimated Time',
        'customer_estimate_seconds' => 'Customer Estimate Time',
    ];

    protected AttachmentService $attachmentService;
    protected NotificationService $notificationService;

    public function __construct(AttachmentService $attachmentService, NotificationService $notificationService)
    {
        $this->attachmentService = $attachmentService;
        $this->notificationService = $notificationService;
        $this->filesystemDisk = env('FILESYSTEM_DISK', 'public');
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['project_flow'] = $data['project_flow'] ?? 'agile';
            $data['priority'] = $data['priority'] ?? 'medium';

            $startDate = $data['start_date'] ?? now(config('constants.timezone'))->toDateString();

            if (empty($data['end_date'])) {
                $data['end_date'] = Carbon::parse($startDate)->addDays(30)->toDateString();
            }

            $defaultStatusId = ProjectStatus::active()->where('is_default', true)->value('id');
            $defaultProjectStageId = ProjectStage::active()->where('is_default', true)->value('id');

            $customer = Customer::find($data['customer_id']);

            $categoryIds = null;
            if (array_key_exists('project_category_ids', $data)) {
                $categoryIds = is_array($data['project_category_ids'])
                    ? array_values(array_unique(array_filter(array_map('intval', $data['project_category_ids']))))
                    : null;
            } elseif (array_key_exists('project_category_id', $data) && $data['project_category_id'] !== null && $data['project_category_id'] !== '') {
                $categoryIds = [(int) $data['project_category_id']];
            }

            $project = Project::create([
                'project_code' => Project::generateProjectCode(),
                'name' => $data['name'],
                'customer_id' => $customer?->id,
                'project_flow' => $data['project_flow'],
                'priority' => $data['priority'],
                'status_id' => $defaultStatusId,
                'project_stage_id' => $defaultProjectStageId,
                'start_date' => $startDate,
                'end_date' => $data['end_date'],
                'sales_person_id' => $customer ? $customer->sales_person_id : null,
                'project_category_ids' => ! empty($categoryIds) ? $categoryIds : null,
                'default_billable' => $data['default_billable'] ?? 1,
            ]);

            //status history
            ProjectStatusHistory::create([
                'project_id' => $project->id,
                'status_id' => $project->status_id,
            ]);

            //stage history
            ProjectStageHistory::create([
                'project_id' => $project->id,
                'stage_id' => $project->project_stage_id,
            ]);

            // Generate Original Timeline for the new project
            ProjectTimeline::create([
                'project_id' => $project->id,
                'name' => 'Original Timeline',
                'type' => 'original',
                'status' => ProjectTimeline::STATUS_ACTIVE,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'customer_end_date' => $project->customer_end_date,
                'estimated_time_seconds' => $project->estimated_time_seconds,
                'customer_estimate_seconds' => $project->customer_estimate_seconds,
                'sort_order' => 1,
                'created_by' => auth()->id(),
            ]);

            // Default Project Notification Preferences
            $this->getNotificationDefaultsForProject($project);

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

            if (array_key_exists('customer_estimate_minutes', $data)) {
                $data['customer_estimate_seconds'] = $data['customer_estimate_minutes'] !== null
                    ? (int) $data['customer_estimate_minutes'] * 60
                    : null;
                unset($data['customer_estimate_minutes']);
            }

            if (array_key_exists('default_task_estimate_minutes', $data)) {
                $data['default_task_estimate_seconds'] = $data['default_task_estimate_minutes'] !== null
                    ? (int) $data['default_task_estimate_minutes'] * 60
                    : null;
                unset($data['default_task_estimate_minutes']);
            }

            // Default values
            $data['customer_id'] = $data['customer_id'] ?? null;

            $projectData = [
                'parent_project_id' => $data['parent_project_id'] ?? null,
                'name' => $data['name'],
                'customer_id' => $data['customer_id'],
                'priority' => $data['priority'],
                'domain' => $data['domain'] ?? null,
                'sales_person_id' => $data['sales_person_id'] ?? null,
                'default_billable' => $data['default_billable'],
            ];

            if (array_key_exists('start_date', $data)) {
                $projectData['start_date'] = $data['start_date'];
            }
            if (array_key_exists('end_date', $data)) {
                $projectData['end_date'] = $data['end_date'];
            }
            if (array_key_exists('estimated_time_seconds', $data)) {
                $projectData['estimated_time_seconds'] = $data['estimated_time_seconds'];
            }
            if (array_key_exists('default_task_estimate_seconds', $data)) {
                $projectData['default_task_estimate_seconds'] = $data['default_task_estimate_seconds'];
            }

            if (array_key_exists('project_category_ids', $data)) {
                $categoryIds = is_array($data['project_category_ids'])
                    ? array_values(array_unique(array_filter(array_map('intval', $data['project_category_ids']))))
                    : null;
                $projectData['project_category_ids'] = ! empty($categoryIds) ? $categoryIds : null;
            } elseif (array_key_exists('project_category_id', $data)) {
                $catId = $data['project_category_id'];
                $projectData['project_category_ids'] = ($catId !== null && $catId !== '') ? [(int) $catId] : null;
            }

            if (array_key_exists('customer_end_date', $data)) {
                $projectData['customer_end_date'] = $data['customer_end_date'];
            }

            if (array_key_exists('customer_estimate_seconds', $data)) {
                $projectData['customer_estimate_seconds'] = $data['customer_estimate_seconds'];
            }

            $originalTimelineValues = $project->only(array_keys(self::PROJECT_TIMELINE_FIELDS));

            // Update project
            $project->fill($projectData);
            $timelineChanges = $this->buildProjectTimelineChanges($project, $originalTimelineValues);
            $project->save();

            // Attach project technologies
            if (isset($data['project_technology_ids'])) {
                $technologyIds = is_array($data['project_technology_ids'])
                    ? array_values(array_unique(array_filter(array_map('intval', $data['project_technology_ids']))))
                    : [];
                $project->technologies()->sync($technologyIds);
            }

            // Attach project service types
            if (array_key_exists('service_type_ids', $data)) {
                $serviceTypeIds = is_array($data['service_type_ids'])
                    ? array_values(array_unique(array_filter(array_map('intval', $data['service_type_ids']))))
                    : [];
                $project->serviceTypes()->sync($serviceTypeIds);
            }

            // Sync original timeline
            app(ProjectTimelineService::class)->syncOriginalTimelineFromProject($project);

            $updatedProject = $project->fresh();

            if ($timelineChanges !== [] && ($actor = auth()->user())) {
                $this->notificationService->notifyProjectTimelineChanged(
                    $updatedProject,
                    $actor,
                    $timelineChanges
                );
            }

            return $updatedProject; // return updated model
        });
    }

    public function buildProjectTimelineChanges(Project $project, array $originalTimelineValues): array
    {
        return collect(self::PROJECT_TIMELINE_FIELDS)
            ->filter(fn($label, $field) => $project->isDirty($field))
            ->map(function ($label, $field) use ($project, $originalTimelineValues) {
                return [
                    'field' => $label,
                    'old' => $this->formatProjectTimelineValue($field, $originalTimelineValues[$field] ?? null),
                    'new' => $this->formatProjectTimelineValue($field, $project->getAttribute($field)),
                ];
            })
            ->values()
            ->all();
    }

    private function formatProjectTimelineValue(string $field, mixed $value): string
    {
        if (in_array($field, ['estimated_time_seconds', 'customer_estimate_seconds'], true)) {
            return $value === null ? '--' : formatSecondsToHoursMinutes((int) $value);
        }

        return AppServiceProvider::formatAppDate($value);
    }

    public function updateStatus(Project $project, int $statusId, ?string $statusDate = null, ?string $remarks = null): Project
    {
        return DB::transaction(function () use ($project, $statusId, $statusDate, $remarks) {
            if ((int) $project->status_id !== $statusId) {
                $fromStatusId = $project->status_id;
                $statusNames = ProjectStatus::withTrashed()
                    ->whereIn('id', [$fromStatusId, $statusId])
                    ->pluck('name', 'id');

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

                if ($actor = auth()->user()) {
                    $this->notificationService->notifyProjectStatusChanged(
                        $project->fresh(),
                        $actor,
                        $statusNames->get($fromStatusId, 'Unknown'),
                        $statusNames->get($statusId, 'Unknown')
                    );
                }
            }

            return $project->fresh();
        });
    }

    public function updateStage(Project $project, ?int $projectStageId, ?string $changeDate = null, ?string $remarks = null): Project
    {
        return DB::transaction(function () use ($project, $projectStageId, $changeDate, $remarks) {
            if ((int) ($project->project_stage_id ?? 0) !== (int) ($projectStageId ?? 0)) {
                $fromStageId = $project->project_stage_id;
                $stageNames = ProjectStage::withTrashed()
                    ->whereIn('id', [$fromStageId, $projectStageId])
                    ->pluck('name', 'id');

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

                if ($actor = auth()->user()) {
                    $this->notificationService->notifyProjectStageChanged(
                        $project->fresh(),
                        $actor,
                        $stageNames->get($fromStageId, 'Unknown'),
                        $stageNames->get($projectStageId, 'Unknown')
                    );
                }
            }

            return $project->fresh();
        });
    }

    public function getTimelines(Project $project): array
    {
        $displayTimeline = $project->display_timeline;

        $taskEstimateQuery = Task::query()
            ->leftJoin('task_statuses', 'task_statuses.id', '=', 'tasks.status_id')
            ->where('tasks.project_id', $project->id)
            ->where('tasks.request_status', Task::REQUEST_APPROVED);

        if ($displayTimeline) {
            $taskEstimateQuery->where('tasks.project_timeline_id', $displayTimeline->id);
        }

        $taskEstimateTotals = $taskEstimateQuery
            ->selectRaw('COALESCE(SUM(tasks.estimated_time_seconds), 0) as total_estimated_seconds')
            ->selectRaw('COALESCE(SUM(CASE WHEN task_statuses.is_completed = 1 THEN tasks.estimated_time_seconds ELSE 0 END), 0) as completed_estimated_seconds')
            ->first();

        $totalEstimatedSeconds = (int) ($taskEstimateTotals?->total_estimated_seconds ?? 0);
        $completedEstimatedSeconds = (int) ($taskEstimateTotals?->completed_estimated_seconds ?? 0);
        $taskCompletionPercentage = $totalEstimatedSeconds > 0
            ? (int) round(($completedEstimatedSeconds / $totalEstimatedSeconds) * 100)
            : 0;

        $startDate = $displayTimeline?->start_date ?? $project->start_date;
        $endDate = $displayTimeline?->end_date ?? $project->end_date;
        $customerEndDate = $displayTimeline?->customer_end_date;

        return [
            'projectTimeline' => $this->buildTimeline($startDate, $endDate),
            'customerTimeline' => $this->buildTimeline($startDate, $customerEndDate),
            'task_progress' => [
                'percentage' => min(max($taskCompletionPercentage, 0), 100),
                'completed_hours' => round($completedEstimatedSeconds / 3600, 2),
                'total_hours' => round($totalEstimatedSeconds / 3600, 2),
            ],
        ];
    }

    public function uploadFile(Project $project, array $data, $category = null)
    {
        $attachments = [];
        if (!empty($data['project_files'])) {
            $directory = 'project_files/' . $project->project_code;

            foreach ($data['project_files'] as $file) {
                $attachment = $this->attachmentService->upload(
                    $file,
                    $directory,
                    $project,
                    $this->filesystemDisk,
                    'public',
                    false,
                    $category
                );
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            }
        }

        return $attachments;
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
                        $this->filesystemDisk,
                        'public',
                        false,
                        'project_note'
                    );
                }
            }

            return $note->load(['attachments', 'addedBy']);
        });
    }

    public function updateNote(ProjectNote $note, array $data): ProjectNote
    {
        return DB::transaction(function () use ($note, $data) {
            $project = $note->project;

            $removedAttachmentIds = $data['removed_attachment_ids'] ?? [];
            if (!empty($removedAttachmentIds)) {
                $attachmentsToDelete = $note->attachments()
                    ->whereIn('id', $removedAttachmentIds)
                    ->get();

                if ($attachmentsToDelete->isNotEmpty()) {
                    $this->attachmentService->delete($attachmentsToDelete);
                }
            }

            $newAttachmentsUploaded = false;
            if (!empty($data['attachments'])) {
                $directory = 'project_files/' . $project->project_code . '/notes';

                foreach ($data['attachments'] as $file) {
                    $this->attachmentService->upload(
                        $file,
                        $directory,
                        $note,
                        $this->filesystemDisk,
                        'public',
                        false,
                        'project_note'
                    );
                }
                $newAttachmentsUploaded = true;
            }

            $attachmentsChanged = !empty($removedAttachmentIds) || $newAttachmentsUploaded;
            $newDescription = $data['description'] ?? null;
            $descriptionChanged = $note->description !== $newDescription;

            if ($descriptionChanged) {
                $note->description = $newDescription;
                $note->updated_by = auth()->id();
                $note->save();
            } elseif ($attachmentsChanged) {
                $note->updated_by = auth()->id();
                $note->updated_at = now();
                $note->saveQuietly();

                activity('project_notes')
                    ->performedOn($note)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'parent_type' => Project::class,
                        'parent_id' => $project->id,
                        'labels' => ['description' => 'Description'],
                        'display_attributes' => ['description' => $note->description],
                    ])
                    ->event('updated')
                    ->log('project_notes.updated');
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

    public function moveTaskToSprint(Project $project, Task $task, ?int $projectSprintId = null, ?int $projectTimelineId = null): Task
    {
        if ((int) $task->project_id !== (int) $project->id) {
            throw new InvalidArgumentException('The provided task does not belong to the given project.');
        }

        $targetTimelineId = $projectTimelineId ?: (int) ($task->project_timeline_id ?? 0);
        $timelineChanged = $targetTimelineId
            && (int) ($task->project_timeline_id ?? 0) !== $targetTimelineId;

        if ($projectTimelineId) {
            $this->resolveEligibleTaskMoveTimeline($project, $projectTimelineId);
        }

        if (! $projectSprintId && ! $timelineChanged) {
            throw new InvalidArgumentException('Please select another timeline or choose a different sprint.');
        }

        if ($projectSprintId && $project->is_linear) {
            throw new InvalidArgumentException('Sprint movement is not available for Linear projects.');
        }

        $projectSprint = null;

        if ($projectSprintId) {
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
        }

        DB::transaction(function () use ($project, $task, $projectSprint, $targetTimelineId, $timelineChanged) {
            $updateData = [];

            if ($projectSprint) {
                $updateData = [
                    'project_sprint_id' => $projectSprint->id,
                    'project_milestone_id' => $projectSprint->project_milestone_id,
                    'sort_order' => Task::nextSortOrder($project->id, (int) $projectSprint->id),
                ];
            }

            if ($timelineChanged) {
                $updateData['project_timeline_id'] = $targetTimelineId;
            }

            $task->update($updateData);

            if ($projectSprint) {
                $this->syncTaskDescendantPlacement(
                    $task,
                    (int) $projectSprint->project_milestone_id,
                    (int) $projectSprint->id,
                    null,
                    null,
                    $timelineChanged,
                    $timelineChanged ? $targetTimelineId : null
                );
            } elseif ($timelineChanged) {
                $this->syncTaskDescendantTimeline($task, $targetTimelineId);
            }
        });

        return $task->fresh();
    }

    public function moveTaskToProject(Project $sourceProject, Project $targetProject, Task $task, ?int $targetMilestoneId = null, ?int $targetSprintId = null, ?int $targetTimelineId = null): Task
    {
        if ((int) $task->project_id !== (int) $sourceProject->id) {
            throw new InvalidArgumentException('The provided task does not belong to the source project.');
        }

        if (! $targetTimelineId) {
            throw new InvalidArgumentException('A target project timeline is required to move this task.');
        }

        $this->resolveEligibleTaskMoveTimeline($targetProject, $targetTimelineId);

        $placement = $this->finalizeTaskPlacement($targetProject, $targetMilestoneId, $targetSprintId);

        $resolvedMilestoneId = $placement['project_milestone_id'];
        $resolvedSprintId = $placement['project_sprint_id'];

        $isFlowChanged = ($sourceProject->project_flow !== $targetProject->project_flow);
        $newStatusId = $isFlowChanged ? $this->resolveTargetStatusIdForFlow($targetProject->project_flow) : null;

        return DB::transaction(function () use ($sourceProject, $targetProject, $task, $resolvedMilestoneId, $resolvedSprintId, $isFlowChanged, $newStatusId, $targetTimelineId) {
            $movingTasks = $this->collectTaskTree($task);

            foreach ($movingTasks as $t) {
                // If assigned to a user, add user to target project team if not exists
                if ($t->current_assignee_id) {
                    $this->ensureUserInProjectTeam($targetProject, (int) $t->current_assignee_id);
                }

                // Stop running task timer ONLY if flow changed
                if ($isFlowChanged) {
                    $this->stopTaskIfRunning($t);
                }
            }

            $isSubtask = $task->parent_task_id !== null;
            $newParentTaskId = $isSubtask ? null : $task->parent_task_id;

            $updateData = [
                'project_id' => $targetProject->id,
                'parent_task_id' => $newParentTaskId,
                'project_milestone_id' => $resolvedMilestoneId,
                'project_sprint_id' => $resolvedSprintId,
                'project_timeline_id' => $targetTimelineId,
                'sort_order' => Task::nextSortOrder($targetProject->id, $resolvedSprintId ? (int) $resolvedSprintId : null),
            ];

            if ($isFlowChanged && $newStatusId) {
                if ((int) $task->status_id !== (int) $newStatusId) {
                    TaskStatusHistory::create([
                        'task_id' => $task->id,
                        'status_id' => $newStatusId,
                        'remarks' => 'Moved from project '.$sourceProject->name,
                    ]);
                }
                $updateData['status_id'] = $newStatusId;
            }

            $task->update($updateData);

            $this->syncTaskDescendantPlacement(
                $task,
                $resolvedMilestoneId ? (int) $resolvedMilestoneId : null,
                $resolvedSprintId ? (int) $resolvedSprintId : null,
                (int) $targetProject->id,
                $isFlowChanged ? $newStatusId : null,
                true,
                $targetTimelineId
            );

            return $task->fresh();
        });
    }

    public function ensureUserInProjectTeam(Project $project, ?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $existing = $project->membersAll()->where('users.id', $userId)->first();

        if ($existing) {
            if ($existing->pivot->removed_at || ! $existing->pivot->is_active) {
                $project->membersAll()->updateExistingPivot($userId, [
                    'is_active' => true,
                    'removed_at' => null,
                    'removed_by' => null,
                ]);
            }
        } else {
            $project->membersAll()->syncWithoutDetaching([
                $userId => [
                    'project_role' => 'member',
                    'is_active' => true,
                ],
            ]);
        }
    }

    public function stopTaskIfRunning(Task $task): void
    {
        $runningLogs = TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->where('is_running', true)
            ->get();

        if ($runningLogs->isEmpty()) {
            return;
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

            if ($log->task_assignment_log_id && class_exists(TaskAssignmentLog::class)) {
                TaskAssignmentLog::query()
                    ->whereKey($log->task_assignment_log_id)
                    ->increment('worked_time_seconds', $duration);
            }

            $totalDuration += $duration;
        }

        if ($totalDuration > 0) {
            $task->increment('actual_time_seconds', $totalDuration);
        }
    }

    public function resolveTargetStatusIdForFlow(string $targetFlowType): ?int
    {
        $defaultStatusId = TaskStatus::query()
            ->active()
            ->where('flow_type', $targetFlowType)
            ->where('is_default', true)
            ->value('id');

        if ($defaultStatusId) {
            return (int) $defaultStatusId;
        }

        $fallbackStatusId = TaskStatus::query()
            ->active()
            ->where('flow_type', $targetFlowType)
            ->where('type', TaskStatus::TYPE_PENDING)
            ->orderBy('sort_order', 'asc')
            ->value('id');

        if ($fallbackStatusId) {
            return (int) $fallbackStatusId;
        }

        return TaskStatus::query()
            ->active()
            ->where('flow_type', $targetFlowType)
            ->orderBy('sort_order', 'asc')
            ->value('id');
    }

    private function collectTaskTree(Task $task): array
    {
        $tasks = [$task];

        foreach ($task->childTasks()->get() as $childTask) {
            $tasks = array_merge($tasks, $this->collectTaskTree($childTask));
        }

        return $tasks;
    }

    private function resolveEligibleTaskMoveTimeline(Project $project, int $projectTimelineId): ProjectTimeline
    {
        $timeline = ProjectTimeline::query()
            ->where('project_id', $project->id)
            ->eligibleForTaskMove()
            ->find($projectTimelineId);

        if (! $timeline) {
            throw new InvalidArgumentException('The selected project timeline is invalid for the destination project.');
        }

        return $timeline;
    }

    private function syncTaskDescendantTimeline(Task $task, int $projectTimelineId): void
    {
        $task->childTasks()
            ->get()
            ->each(function (Task $childTask) use ($projectTimelineId) {
                $childTask->update(['project_timeline_id' => $projectTimelineId]);
                $this->syncTaskDescendantTimeline($childTask, $projectTimelineId);
            });
    }

    public function syncTaskPlacementToDescendants(Task $task): void
    {
        $this->syncTaskDescendantPlacement(
            $task,
            $task->project_milestone_id ? (int) $task->project_milestone_id : null,
            $task->project_sprint_id ? (int) $task->project_sprint_id : null,
            (int) $task->project_id
        );
    }

    private function syncTaskDescendantPlacement(Task $task, ?int $projectMilestoneId, ?int $projectSprintId, ?int $projectId = null, ?int $newStatusId = null, bool $updateTimeline = false, ?int $targetTimelineId = null): void
    {
        $targetProjectId = $projectId ?: (int) $task->project_id;

        $task->childTasks()
            ->get()
            ->each(function (Task $childTask) use ($projectMilestoneId, $projectSprintId, $targetProjectId, $newStatusId, $updateTimeline, $targetTimelineId) {
                $childUpdateData = [
                    'project_id' => $targetProjectId,
                    'project_milestone_id' => $projectMilestoneId,
                    'project_sprint_id' => $projectSprintId,
                ];

                if ($updateTimeline) {
                    $childUpdateData['project_timeline_id'] = $targetTimelineId;
                }

                if ($newStatusId) {
                    if ((int) $childTask->status_id !== (int) $newStatusId) {
                        TaskStatusHistory::create([
                            'task_id' => $childTask->id,
                            'status_id' => $newStatusId,
                        ]);
                    }
                    $childUpdateData['status_id'] = $newStatusId;
                }

                $childTask->update($childUpdateData);

                $this->syncTaskDescendantPlacement($childTask, $projectMilestoneId, $projectSprintId, $targetProjectId, $newStatusId, $updateTimeline, $targetTimelineId);
            });
    }

    private function buildTimeline(?Carbon $startDate, ?Carbon $targetDate): array
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
            'pending_requests_count' => $project->tasks()->where('request_status', Task::REQUEST_PENDING)->count(),
            'scope_files_count' => $project->attachments()->where('category', 'scope_files')->count(),
        ];
    }

    /**
     * Get default notification preferences for a project.
     * 
     * @param  Project  $project
     */
    public function getNotificationDefaultsForProject(Project $project): void
    {
        $notifications = config('project_constants.project_notification_types', []);

        foreach ($notifications as $key => $value) {
            // Find a default matching the project's flow first.
            $default = ProjectNotificationDefault::query()
                ->where('setting_key', $key)
                ->whereJsonContains('conditions->project_flow', $project->project_flow)
                ->first();

            // If no flow-specific default exists, use the general default (conditions = []).
            if (!$default) {
                $default = ProjectNotificationDefault::query()
                    ->where('setting_key', $key)
                    ->first();
            }

            // Use false only when no matching default exists.
            $isEnabled = data_get($default?->setting_value, 'is_enabled', false);

            ProjectNotificationPreference::create([
                'project_id' => $project->id,
                'notification_type' => $key,
                'is_enabled' => $isEnabled,
            ]);
        }
    }

    public const TASK_GROUPS_PER_PAGE = 5;
    public const TASKS_PER_GROUP_PAGE = 5;

    public function getMatchingRootTaskIds(Project $project, string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return [];
        }

        $matchingTasks = Task::query()
            ->where('project_id', $project->id)
            ->accessibleBy(auth()->user())
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->get(['id', 'parent_task_id']);

        if ($matchingTasks->isEmpty()) {
            return [];
        }

        $rootIds = $matchingTasks->whereNull('parent_task_id')->pluck('id')->all();
        $childParentIds = $matchingTasks->whereNotNull('parent_task_id')->pluck('parent_task_id')->unique()->filter()->all();

        while (!empty($childParentIds)) {
            $parents = Task::query()
                ->where('project_id', $project->id)
                ->whereIn('id', $childParentIds)
                ->get(['id', 'parent_task_id']);

            $childParentIds = [];
            foreach ($parents as $p) {
                if ($p->parent_task_id === null) {
                    $rootIds[] = $p->id;
                } else {
                    $childParentIds[] = $p->parent_task_id;
                }
            }
        }

        return array_values(array_unique($rootIds));
    }

    public function getTasksTabViewData(Project $project, ?string $preferredGroupKey = null, ?string $search = null): array
    {
        $search = filled($search) ? trim((string) $search) : null;
        $isLinearFlow = $project->project_flow === 'linear';
        $taskGroupViewData = $this->getInitialTaskGroupViewData($project, $preferredGroupKey, $search);
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

        $projectTimelines = ProjectTimeline::query()
            ->where('project_id', $project->id)
            ->eligibleForTaskMove()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'status']);

        $initialGroupKey = $taskGroupViewData['initialGroupKey'];
        $initialTasks = $taskGroupViewData['initialTasks'] ?? collect();
        $initialTasksPagination = $taskGroupViewData['initialTasksPagination'] ?? [
            'page' => 1,
            'next_page' => null,
            'has_more_pages' => false,
        ];

        $isDeletedProjectView = $project->trashed();
        $taskGroupsPageUrl = $isDeletedProjectView
            ? route('projects.restore.tasks.groups.index', ['id' => $project->id])
            : route('projects.tasks.groups.index', $project);

        if ($search !== null) {
            $taskGroupsPageUrl .= (str_contains($taskGroupsPageUrl, '?') ? '&' : '?') . 'search=' . urlencode($search);
        }

        return [
            'project' => $project,
            'taskGroups' => $taskGroups,
            'initialGroupKey' => $initialGroupKey,
            'initialTasks' => $initialTasks,
            'initialTasksPagination' => $initialTasksPagination,
            'totalTaskCount' => $taskGroupViewData['totalTaskCount'],
            'sprintCount' => $taskGroupViewData['sprintCount'],
            'isLinearFlow' => $isLinearFlow,
            'projectMilestones' => $projectMilestones,
            'projectSprints' => $projectSprints,
            'projectTimelines' => $projectTimelines,
            'taskGroupsPagination' => $taskGroupViewData['pagination'],
            'taskGroupsPageUrl' => $taskGroupsPageUrl,
            'search' => $search,
        ];
    }

    public function renderTasksTab(Project $project, ?string $preferredGroupKey = null, ?string $search = null): string
    {
        $viewData = $this->getTasksTabViewData($project, $preferredGroupKey, $search);

        return view('projects.partials.tabs.tasks', $viewData)->render();
    }

    public function getTasksTabResponse(Project $project, Request $request): array
    {
        $preferredGroup = $request->filled('preferred_group') ? (string) $request->input('preferred_group') : null;
        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;

        $viewData = $this->getTasksTabViewData($project, $preferredGroup, $search);

        return [
            'status' => true,
            'total_count' => $viewData['totalTaskCount'],
            'sprint_count' => $viewData['sprintCount'],
            'tasks_html' => view('projects.partials.tasks.group-list', $viewData)->render(),
            'html' => view('projects.partials.tabs.tasks', $viewData)->render(),
        ];
    }

    public function getInitialTaskGroupViewData(Project $project, ?string $preferredGroupKey = null, ?string $search = null): array
    {
        $search = filled($search) ? trim((string) $search) : null;

        if ($project->project_flow === 'linear') {
            $rootTaskIds = $search !== null ? $this->getMatchingRootTaskIds($project, $search) : null;
            if ($search !== null && empty($rootTaskIds)) {
                return [
                    'taskGroups' => collect(),
                    'initialGroupKey' => null,
                    'initialTasks' => collect(),
                    'initialTasksPagination' => ['page' => 1, 'next_page' => null, 'has_more_pages' => false],
                    'totalTaskCount' => 0,
                    'sprintCount' => 0,
                    'pagination' => ['page' => 1, 'next_page' => null, 'has_more_pages' => false],
                ];
            }

            $taskGroup = $this->buildLinearTaskGroup($project, $search, $rootTaskIds);
            $taskPage = $this->getTaskGroupTaskPage($project, 'all-tasks', 1, self::TASKS_PER_GROUP_PAGE, $search, $rootTaskIds);
            $taskGroup['tasks'] = $taskPage['tasks'];
            $taskGroup['pagination'] = $taskPage['pagination'];

            return [
                'taskGroups' => collect([$taskGroup]),
                'initialGroupKey' => 'all-tasks',
                'initialTasks' => $taskPage['tasks'],
                'initialTasksPagination' => $taskPage['pagination'],
                'totalTaskCount' => $taskGroup['task_count'],
                'sprintCount' => 0,
                'pagination' => [
                    'page' => 1,
                    'next_page' => null,
                    'has_more_pages' => false,
                ],
            ];
        }

        if ($search !== null) {
            $rootTaskIds = $this->getMatchingRootTaskIds($project, $search);
            if (empty($rootTaskIds)) {
                return [
                    'taskGroups' => collect(),
                    'initialGroupKey' => null,
                    'initialTasks' => collect(),
                    'initialTasksPagination' => ['page' => 1, 'next_page' => null, 'has_more_pages' => false],
                    'totalTaskCount' => 0,
                    'sprintCount' => 0,
                    'pagination' => ['page' => 1, 'next_page' => null, 'has_more_pages' => false],
                ];
            }

            $sprintCounts = Task::query()
                ->where('project_id', $project->id)
                ->accessibleBy(auth()->user())
                ->whereIn('id', $rootTaskIds)
                ->whereNotNull('project_sprint_id')
                ->selectRaw('project_sprint_id, count(*) as count')
                ->groupBy('project_sprint_id')
                ->pluck('count', 'project_sprint_id');

            $ungroupedCount = Task::query()
                ->where('project_id', $project->id)
                ->accessibleBy(auth()->user())
                ->whereIn('id', $rootTaskIds)
                ->whereNull('project_sprint_id')
                ->count();

            $sprintIds = $sprintCounts->keys()->all();
            $projectSprints = ProjectSprint::query()
                ->where('project_id', $project->id)
                ->whereIn('id', $sprintIds)
                ->with(['projectMilestone:id,name'])
                ->orderForDisplay()
                ->get();

            $taskGroups = $projectSprints->values()->map(function (ProjectSprint $projectSprint, int $index) use ($project, $sprintCounts, $search, $rootTaskIds) {
                $group = $this->mapProjectSprintToTaskGroup($projectSprint, $index);
                $group['task_count'] = (int) ($sprintCounts[$projectSprint->id] ?? 0);
                $taskPage = $this->getTaskGroupTaskPage($project, $group['key'], 1, self::TASKS_PER_GROUP_PAGE, $search, $rootTaskIds);
                $group['tasks'] = $taskPage['tasks'];
                $group['pagination'] = $taskPage['pagination'];
                return $group;
            });

            if ($ungroupedCount > 0) {
                $ungroupedTasks = Task::query()
                    ->where('project_id', $project->id)
                    ->whereNull('project_sprint_id')
                    ->accessibleBy(auth()->user())
                    ->whereIn('id', $rootTaskIds);
                $ungroupedGroup = $this->buildUngroupedTaskGroup($ungroupedTasks, $taskGroups->isEmpty());
                $ungroupedGroup['task_count'] = $ungroupedCount;
                $taskPage = $this->getTaskGroupTaskPage($project, 'ungrouped', 1, self::TASKS_PER_GROUP_PAGE, $search, $rootTaskIds);
                $ungroupedGroup['tasks'] = $taskPage['tasks'];
                $ungroupedGroup['pagination'] = $taskPage['pagination'];
                $taskGroups->push($ungroupedGroup);
            }

            $totalTaskCount = (int) Task::query()
                ->where('project_id', $project->id)
                ->accessibleBy(auth()->user())
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                })
                ->count();

            $firstGroup = $taskGroups->first();
            $initialGroupKey = $firstGroup ? $firstGroup['key'] : null;

            return [
                'taskGroups' => $taskGroups,
                'initialGroupKey' => $initialGroupKey,
                'initialTasks' => $firstGroup['tasks'] ?? collect(),
                'initialTasksPagination' => $firstGroup['pagination'] ?? ['page' => 1, 'next_page' => null, 'has_more_pages' => false],
                'totalTaskCount' => $totalTaskCount,
                'sprintCount' => $projectSprints->count(),
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

        $initialTaskPage = $initialGroupKey
            ? $this->getTaskGroupTaskPage($project, $initialGroupKey, 1, self::TASKS_PER_GROUP_PAGE)
            : ['tasks' => collect(), 'pagination' => ['page' => 1, 'next_page' => null, 'has_more_pages' => false]];

        $totalTaskCount = (int) Task::query()
            ->where('project_id', $project->id)
            ->accessibleBy(auth()->user())
            ->count();
        $hasMorePages = $loadedSprintLimit < $sprintCount;

        return [
            'taskGroups' => $taskGroups,
            'initialGroupKey' => $initialGroupKey,
            'initialTasks' => $initialTaskPage['tasks'],
            'initialTasksPagination' => $initialTaskPage['pagination'],
            'totalTaskCount' => $totalTaskCount,
            'sprintCount' => $sprintCount,
            'pagination' => [
                'page' => $loadedPages,
                'next_page' => $hasMorePages ? $loadedPages + 1 : null,
                'has_more_pages' => $hasMorePages,
            ],
        ];
    }

    public function buildTaskGroupTasksQuery(Project $project, string $groupKey, ?string $search = null, ?array $rootTaskIds = null)
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

        if ($search !== null && trim($search) !== '') {
            $ids = $rootTaskIds ?? $this->getMatchingRootTaskIds($project, $search);
            $query->whereIn('id', $ids);
        }

        if ($groupKey === 'ungrouped') {
            $query->whereNull('project_sprint_id');
        } elseif ($groupKey === 'all-tasks') {
            // Linear-flow projects display a flat task list without sprint grouping.
        } else {
            abort_unless(str_starts_with($groupKey, 'sprint-'), 404);

            $projectSprintId = (int) str_replace('sprint-', '', $groupKey);

            abort_unless(
                $project->projectSprints()->whereKey($projectSprintId)->exists(),
                404
            );

            $query->where('project_sprint_id', $projectSprintId);
        }

        return $query;
    }

    public function getTaskGroupTaskPage(Project $project, string $groupKey, int $page, int $perPage, ?string $search = null, ?array $rootTaskIds = null): array
    {
        $page = max($page, 1);
        $perPage = max($perPage, 1);
        $query = $this->buildTaskGroupTasksQuery($project, $groupKey, $search, $rootTaskIds);
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

    public function findTaskGroup(Project $project, string $groupKey, ?string $search = null): ?array
    {
        if ($groupKey === 'all-tasks' && $project->project_flow === 'linear') {
            $rootTaskIds = $search !== null ? $this->getMatchingRootTaskIds($project, $search) : null;
            return $this->buildLinearTaskGroup($project, $search, $rootTaskIds);
        }

        if ($groupKey === 'ungrouped' && $project->project_flow !== 'linear') {
            $ungroupedTasks = Task::query()
                ->where('project_id', $project->id)
                ->whereNull('project_sprint_id')
                ->accessibleBy(auth()->user());

            if ($search !== null && trim($search) !== '') {
                $rootTaskIds = $this->getMatchingRootTaskIds($project, $search);
                $ungroupedTasks->whereIn('id', $rootTaskIds);
            }

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

        $group = $this->mapProjectSprintToTaskGroup($projectSprint, max(($position === false ? 0 : $position), 0));

        if ($search !== null && trim($search) !== '') {
            $rootTaskIds = $this->getMatchingRootTaskIds($project, $search);
            $group['task_count'] = Task::query()
                ->where('project_id', $project->id)
                ->where('project_sprint_id', $projectSprintId)
                ->accessibleBy(auth()->user())
                ->whereIn('id', $rootTaskIds)
                ->count();
        }

        return $group;
    }

    public function resolveTaskGroupLoadedPages(Project $project, ?string $preferredGroupKey, int $perPage): int
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

    public function getSprintCount(Project $project): int
    {
        return (int) ProjectSprint::query()
            ->where('project_id', $project->id)
            ->count();
    }

    public function getOrderedProjectSprintIds(Project $project): Collection
    {
        return ProjectSprint::query()
            ->where('project_id', $project->id)
            ->orderForDisplay()
            ->pluck('id')
            ->values();
    }

    public function buildLinearTaskGroup(Project $project, ?string $search = null, ?array $rootTaskIds = null): array
    {
        $allTasks = Task::query()
            ->where('project_id', $project->id)
            ->accessibleBy(auth()->user());

        if ($search !== null && trim($search) !== '') {
            $ids = $rootTaskIds ?? $this->getMatchingRootTaskIds($project, $search);
            $allTasks->whereIn('id', $ids);
        }

        $taskCount = (clone $allTasks)->count();
        $derivedSeconds = (int) (clone $allTasks)->sum('estimated_time_seconds');
        $actualSeconds = (int) (clone $allTasks)->sum('actual_time_seconds');

        $displayTimeline = $project->display_timeline;
        $timelineEstimate = $displayTimeline?->estimated_time_seconds;
        $hasEstimate = $displayTimeline !== null && $timelineEstimate !== null && (int) $timelineEstimate > 0;
        $estimatedSeconds = $timelineEstimate !== null ? (int) $timelineEstimate : null;
        $derivedColorClass = $this->resolveDerivedColorClass($estimatedSeconds, $derivedSeconds, $hasEstimate);

        return [
            'key' => 'all-tasks',
            'sprint_id' => null,
            'name' => 'All Tasks',
            'subtitle' => null,
            'accent_color' => '#3B82F6',
            'task_count' => $taskCount,
            'estimated_seconds' => $estimatedSeconds,
            'estimated_label' => $this->formatSecondsShort((int) ($estimatedSeconds ?? 0)),
            'derived_seconds' => $derivedSeconds,
            'derived_label' => $this->formatSecondsShort($derivedSeconds),
            'actual_seconds' => $actualSeconds,
            'actual_label' => $this->formatSecondsShort($actualSeconds),
            'has_estimate' => $hasEstimate,
            'derived_color_class' => $derivedColorClass,
            'date_label' => null,
            'created_label' => null,
            'is_latest' => true,
            'is_unscheduled' => false,
            'is_linear_group' => true,
        ];
    }

    public function mapProjectSprintToTaskGroup(ProjectSprint $projectSprint, int $index): array
    {
        $sprintEstimatedSeconds = $projectSprint->estimated_time_seconds !== null
            ? (int) $projectSprint->estimated_time_seconds
            : null;
        $sprintDerivedSeconds = (int) ($projectSprint->derived_time_seconds ?? 0);
        $sprintActualSeconds = (int) ($projectSprint->actual_time_seconds ?? 0);
        $hasEstimate = $sprintEstimatedSeconds !== null && $sprintEstimatedSeconds > 0;
        $derivedColorClass = $this->resolveDerivedColorClass($sprintEstimatedSeconds, $sprintDerivedSeconds, $hasEstimate);

        return [
            'key' => 'sprint-' . $projectSprint->id,
            'sprint_id' => $projectSprint->id,
            'name' => $projectSprint->name,
            'subtitle' => $projectSprint->projectMilestone?->name,
            'accent_color' => $projectSprint->color ?: '#D1D5DB',
            'task_count' => (int) $projectSprint->tasks_count,
            'estimated_seconds' => $sprintEstimatedSeconds,
            'estimated_label' => $this->formatSecondsShort((int) ($sprintEstimatedSeconds ?? 0)),
            'derived_seconds' => $sprintDerivedSeconds,
            'derived_label' => $this->formatSecondsShort($sprintDerivedSeconds),
            'actual_seconds' => $sprintActualSeconds,
            'actual_label' => $this->formatSecondsShort($sprintActualSeconds),
            'has_estimate' => $hasEstimate,
            'derived_color_class' => $derivedColorClass,
            'date_label' => $this->formatDateRange($projectSprint->start_date, $projectSprint->end_date),
            'created_label' => $projectSprint->created_at
                ? AppServiceProvider::formatAppDate($projectSprint->created_at)
                : null,
            'is_latest' => $index === 0,
            'is_unscheduled' => false,
            'is_linear_group' => false,
        ];
    }

    public function buildUngroupedTaskGroup($ungroupedTasks, bool $isLatest): array
    {
        $ungroupedEstimatedSeconds = (int) (clone $ungroupedTasks)->sum('estimated_time_seconds');
        $ungroupedDerivedSeconds = (int) (clone $ungroupedTasks)->sum('derived_time_seconds');
        $ungroupedActualSeconds = (int) (clone $ungroupedTasks)->sum('actual_time_seconds');
        $hasEstimate = $ungroupedEstimatedSeconds > 0;
        $derivedColorClass = $this->resolveDerivedColorClass($ungroupedEstimatedSeconds, $ungroupedDerivedSeconds, $hasEstimate);

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
            'has_estimate' => $hasEstimate,
            'derived_color_class' => $derivedColorClass,
            'date_label' => 'No sprint dates',
            'created_label' => null,
            'is_latest' => $isLatest,
            'is_unscheduled' => true,
            'is_linear_group' => false,
        ];
    }

    public function resolveDerivedColorClass(?int $estimatedSeconds, int $derivedSeconds, bool $hasEstimate): string
    {
        if (! $hasEstimate || $estimatedSeconds === null || $estimatedSeconds <= 0) {
            return '';
        }

        return $derivedSeconds > $estimatedSeconds
            ? 'text-error-300 dark:text-red-400'
            : 'text-success-400 dark:text-success-300';
    }

    public function loadTaskDescendantsForGroup(Task $task, array $taskRowRelations): void
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

    public function getProjectTaskRowRelations(): array
    {
        return [
            'currentAssignee.primaryAttachment',
            'status',
            'taskType:id,name,code,color',
            'taskMode:id,name,code,color',
            'tags',
            'projectMilestone:id,name,is_backlog,is_system',
            'projectSprint:id,name,project_milestone_id,is_backlog,is_system',
            'projectTimeline:id,name',
            'parentTask:id,name',
        ];
    }

    public function formatSecondsShort(int $seconds): string
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

    public function formatDateRange($startDate, $endDate): string
    {
        if ($startDate && $endDate) {
            return AppServiceProvider::formatAppDate($startDate) . ' - ' . AppServiceProvider::formatAppDate($endDate);
        }

        if ($startDate) {
            return 'Starts ' . AppServiceProvider::formatAppDate($startDate);
        }

        if ($endDate) {
            return 'Ends ' . AppServiceProvider::formatAppDate($endDate);
        }

        return 'No sprint dates';
    }
}

<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use App\Traits\TaskFilterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Traits\HasFormOptions;

class Task extends Model
{
    use SoftDeletes, TaskFilterable, Sortable, LogsModelActivity, HasFormOptions;

    protected ?int $previousProjectMilestoneIdForMetrics = null;

    protected ?int $previousProjectSprintIdForMetrics = null;

    protected $fillable = [
        'project_id',
        'project_milestone_id',
        'project_sprint_id',
        'parent_task_id',
        'name',
        'code',
        'description',
        'status_id',
        'task_type_id',
        'task_mode_id',
        'priority',
        'current_assignee_id',
        'due_date_time',
        'completed_at',
        'estimated_time_seconds',
        'derived_time_seconds',
        'actual_time_seconds',
        'is_billable',
        'request_type',
        'request_status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'sort_order',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'name',
        'code',
        'project.name',
        'currentAssignee.name',
        'status.name',
        'priority',
        'estimated_time_seconds',
        'actual_time_seconds',
        'due_date_time',
        'sort_order',
        'created_at',
    ];

    protected $searchable = [
        'name',
        'code',
        'description',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'project_milestone_id' => 'integer',
        'project_sprint_id' => 'integer',
        'parent_task_id' => 'integer',
        'status_id' => 'integer',
        'current_assignee_id' => 'integer',
        'due_date_time' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_time_seconds' => 'integer',
        'derived_time_seconds' => 'integer',
        'actual_time_seconds' => 'integer',
        'is_billable' => 'boolean',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'rejected_by' => 'integer',
        'rejected_at' => 'datetime',
        'sort_order' => 'integer',
        'added_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Task $task) {
            $task->added_by = Auth::id();

            if (blank($task->code)) {
                $project = $task->relationLoaded('project')
                    ? $task->project
                    : Project::find($task->project_id);

                $task->code = self::generateTaskCode($project);
            }

            if (blank($task->sort_order)) {
                $task->sort_order = self::nextSortOrder(
                    (int) $task->project_id,
                    $task->project_sprint_id ? (int) $task->project_sprint_id : null
                );
            }
        });

        static::updating(function (Task $task) {
            $task->updated_by = Auth::id();
            $task->previousProjectMilestoneIdForMetrics = $task->getOriginal('project_milestone_id')
                ? (int) $task->getOriginal('project_milestone_id')
                : null;
            $task->previousProjectSprintIdForMetrics = $task->getOriginal('project_sprint_id')
                ? (int) $task->getOriginal('project_sprint_id')
                : null;
        });

        static::saved(function (Task $task) {
            $task->refreshPlacementMetrics();
        });

        static::deleted(function (Task $task) {
            $task->refreshPlacementMetrics();
        });

        static::restored(function (Task $task) {
            $task->refreshPlacementMetrics();
        });
    }

    public static function normalizeTaskDueDateTime(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return Carbon::parse(
            (string) $value,
            (string) config('constants.timezone', config('app.timezone'))
        )
            ->timezone((string) config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }

    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->is_super_admin || $user->can('task.view_all_tasks')) {
            return $query;
        }

        return $query->where(function ($taskQuery) use ($user) {
            $taskQuery
                ->where('current_assignee_id', $user->id)
                ->orWhereHas('project.teamLeader', function ($teamLeaderQuery) use ($user) {
                    $teamLeaderQuery->whereKey($user->id);
                })
                ->orWhereHas('projectMilestone', function ($moduleQuery) use ($user) {
                    $moduleQuery->where('owner_id', $user->id);
                })
                ->orWhereExists(function ($sub) use ($user) {
                    $sub->selectRaw(1)
                        ->from('task_assignment_logs')
                        ->whereColumn('task_assignment_logs.task_id', 'tasks.id')
                        ->where('task_assignment_logs.user_id', $user->id)
                        ->where('task_assignment_logs.worked_time_seconds', '>', 0);
                });
        });
    }

    public function refreshPlacementMetrics(): void
    {
        $sprintIds = collect([
            $this->project_sprint_id ? (int) $this->project_sprint_id : null,
            $this->previousProjectSprintIdForMetrics,
        ])
            ->filter()
            ->unique()
            ->values();

        $moduleIds = collect([
            $this->project_milestone_id ? (int) $this->project_milestone_id : null,
            $this->previousProjectMilestoneIdForMetrics,
        ]);

        if ($sprintIds->isNotEmpty()) {
            $sprints = ProjectSprint::query()
                ->whereIn('id', $sprintIds->all())
                ->get();

            foreach ($sprints as $projectSprint) {
                $projectSprint->refreshDerivedTimeSeconds();
                $moduleIds->push($projectSprint->project_milestone_id ? (int) $projectSprint->project_milestone_id : null);
            }
        }

        $moduleIds
            ->filter()
            ->unique()
            ->values()
            ->whenNotEmpty(fn($ids) => ProjectMilestone::query()
                ->whereIn('id', $ids->all())
                ->get()
                ->each(fn(ProjectMilestone $projectMilestone) => $projectMilestone->refreshTrackedTimeMetrics()));

        $this->previousProjectMilestoneIdForMetrics = null;
        $this->previousProjectSprintIdForMetrics = null;
    }

    public static function generateTaskCode(?Project $project = null): string
    {
        $projectCodeSegment = self::resolveProjectCodeSegment($project);
        $nextNumber = self::nextTaskCodeNumber();

        do {
            $taskCode = sprintf('TSK-%s-%05d', $projectCodeSegment, $nextNumber);
            $nextNumber++;
        } while (self::withTrashed()->where('code', $taskCode)->exists());

        return $taskCode;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projectMilestone()
    {
        return $this->belongsTo(ProjectMilestone::class);
    }

    public function projectSprint()
    {
        return $this->belongsTo(ProjectSprint::class);
    }

    public function parentTask()
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function childTasks()
    {
        return $this->hasMany(self::class, 'parent_task_id')->orderBy('sort_order');
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class, 'status_id')->withTrashed();
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class, 'task_type_id')->withTrashed();
    }

    public function taskMode()
    {
        return $this->belongsTo(TaskMode::class, 'task_mode_id')->withTrashed();
    }

    public function currentAssignee()
    {
        return $this->belongsTo(User::class, 'current_assignee_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function assignmentLogs()
    {
        return $this->hasMany(TaskAssignmentLog::class)->latest('assigned_from');
    }

    public function currentAssignmentLog()
    {
        return $this->hasOne(TaskAssignmentLog::class)->where('is_current', true);
    }

    public function isPendingApproval(): bool
    {
        return $this->request_status === 'pending';
    }

    public function isApprovedRequest(): bool
    {
        return $this->request_status === 'approved';
    }

    public function isRejectedRequest(): bool
    {
        return $this->request_status === 'rejected';
    }

    protected function applyFilterSearchExtensions(Builder $query, string $search, string $condition): void
    {
        if ($condition === 'not_contains') {
            return;
        }

        $matchingAncestorIds = $this->getMatchingAncestorTaskIds($search, $condition);

        if ($matchingAncestorIds === []) {
            return;
        }

        $query->orWhereIn($this->qualifyColumn('id'), $matchingAncestorIds);
    }

    protected function applySearchCondition(Builder $query, string $column, string $search, string $condition): void
    {
        switch ($condition) {
            case 'starts_with':
                $query->where($column, 'like', $search . '%');
                break;

            case 'ends_with':
                $query->where($column, 'like', '%' . $search);
                break;

            default:
                $query->where($column, 'like', '%' . $search . '%');
                break;
        }
    }

    protected function getMatchingAncestorTaskIds(string $search, string $condition): array
    {
        $matchingTasks = static::query()
            ->select(['id', 'parent_task_id'])
            ->whereNotNull('parent_task_id')
            ->where(function (Builder $query) use ($search, $condition) {
                $this->applySearchCondition($query, 'name', $search, $condition);
            })
            ->get();

        if ($matchingTasks->isEmpty()) {
            return [];
        }

        $topLevelAncestorIds = [];
        $pendingIds = $matchingTasks
            ->pluck('parent_task_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        while ($pendingIds !== []) {
            $parentRows = static::query()
                ->select(['id', 'parent_task_id'])
                ->whereIn('id', $pendingIds)
                ->get();

            $nextPendingIds = [];

            foreach ($parentRows as $parentRow) {
                $parentId = (int) $parentRow->id;
                $ancestorParentId = $parentRow->parent_task_id ? (int) $parentRow->parent_task_id : null;

                if ($ancestorParentId === null) {
                    $topLevelAncestorIds[] = $parentId;
                    continue;
                }

                $nextPendingIds[] = $ancestorParentId;
            }

            $pendingIds = array_values(array_unique($nextPendingIds));
        }

        return array_values(array_unique($topLevelAncestorIds));
    }

    public function timeLogs()
    {
        return $this->hasMany(TaskTimeLog::class)->latest('started_at');
    }

    public function statusHistories()
    {
        return $this->hasMany(TaskStatusHistory::class)->orderBy('added_at', 'desc');
    }

    public function latestStatusHistory()
    {
        return $this->hasOne(TaskStatusHistory::class)->latestOfMany();
    }

    public function activeTimeLog()
    {
        return $this->hasOne(TaskTimeLog::class)->where('is_running', true);
    }

    public function isRunningBy($userId)
    {
        return $this->timeLogs()->where('user_id', $userId)->where('is_running', 1)->exists();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'task_tags')
            ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id')->orderBy('created_at', 'desc');
    }

    public function taskNotes()
    {
        return $this->hasMany(TaskNote::class, 'task_id')->orderBy('created_at', 'desc');
    }

    public function tagLinks()
    {
        return $this->hasMany(TaskTag::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getEstimatedTimeFormattedAttribute(): string
    {
        return $this->formatSeconds($this->estimated_time_seconds);
    }

    public function getDerivedTimeFormattedAttribute(): string
    {
        return $this->formatSeconds($this->derived_time_seconds);
    }

    public function getActualTimeFormattedAttribute(): string
    {
        return $this->formatSeconds($this->actual_time_seconds);
    }

    public static function nextSortOrder(int $projectId, ?int $projectSprintId = null): int
    {
        $query = self::query()
            ->where('project_id', $projectId);

        if ($projectSprintId) {
            $query->where('project_sprint_id', $projectSprintId);
        } else {
            $query->whereNull('project_sprint_id');
        }

        return ((int) $query->max('sort_order')) + 1;
    }

    private static function nextTaskCodeNumber(): int
    {
        $lastTaskCode = self::withTrashed()
            ->whereNotNull('code')
            ->orderByDesc('id')
            ->value('code');

        if (! $lastTaskCode || ! preg_match('/(\d+)$/', $lastTaskCode, $matches)) {
            return 1;
        }

        return ((int) $matches[1]) + 1;
    }

    private static function resolveProjectCodeSegment(?Project $project): string
    {
        $projectCode = $project?->project_code
            ? strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $project->project_code))
            : 'GEN';

        return substr($projectCode, 0, 20) ?: 'GEN';
    }

    private function formatSeconds(?int $seconds): string
    {
        $totalSeconds = max(0, (int) ($seconds ?? 0));
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
    }

    // Get all related users for notifications (assignee, reporter, manager, project team leader, module owner)
    public function getRelatedUsers()
    {
        $this->loadMissing([
            'addedBy',
            'currentAssignee.reporter',
            'currentAssignee.manager',
            'project.teamLeader',
            'projectMilestone.owner',
        ]);

        return collect([
            $this->addedBy,
            $this->currentAssignee,
            $this->currentAssignee?->reporter,
            $this->currentAssignee?->manager,
            $this->project?->teamLeader,
            $this->projectMilestone?->owner,
        ])
            ->filter()
            ->unique('id')
            ->values();
    }

    /*----------------Activity Log Customization----------------*/

    // Never show these fields in activity log details.
    protected array $activityLogExceptAttributes = [
        'code',
        'description',
        'derived_time_seconds',
        'actual_time_seconds',
        'added_by',
        'updated_by',
    ];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'project_milestone_id' => 'Milestone',
            'project_sprint_id' => 'Sprint',
            'parent_task_id' => 'Parent Task',
            'estimated_time_seconds' => 'Estimated Time',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'project_id' => $this->project?->name ?? $value,
            'project_milestone_id' => ProjectMilestone::withTrashed()->find($value)?->name ?? $value,
            'project_sprint_id' => ProjectSprint::withTrashed()->find($value)?->name ?? $value,
            'parent_task_id' => Task::find($value)?->name ?? $value,
            'status_id' => TaskStatus::find($value)?->name ?? $value,
            'task_type_id' => TaskType::find($value)?->name ?? $value,
            'task_mode_id' => TaskMode::find($value)?->name ?? $value,
            'current_assignee_id' => User::find($value)?->name ?? $value,
            'estimated_time_seconds' => $this->secondsToReadable($value),
            default => $value,
        };
    }

    protected function getActivityParent(): array
    {
        return [
            'type' => Project::class,
            'id' => $this->project_id,
        ];
    }
}

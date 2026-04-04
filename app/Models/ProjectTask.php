<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ProjectTask extends Model
{
    use SoftDeletes, Filterable, Sortable, LogsModelActivity;

    protected $fillable = [
        'project_id',
        'project_module_id',
        'project_sprint_id',
        'parent_task_id',
        'title',
        'code',
        'description',
        'status_id',
        'task_type',
        'task_mode',
        'priority',
        'current_assignee_id',
        'start_date',
        'due_date',
        'completed_at',
        'estimated_time_seconds',
        'derived_time_seconds',
        'actual_time_seconds',
        'is_billable',
        'sort_order',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'title',
        'code',
        'priority',
        'start_date',
        'due_date',
        'sort_order',
        'created_at',
    ];

    protected $searchable = [
        'title',
        'code',
        'description',
        'task_type',
        'task_mode',
        'priority',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'project_module_id' => 'integer',
        'project_sprint_id' => 'integer',
        'parent_task_id' => 'integer',
        'status_id' => 'integer',
        'current_assignee_id' => 'integer',
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'estimated_time_seconds' => 'integer',
        'derived_time_seconds' => 'integer',
        'actual_time_seconds' => 'integer',
        'is_billable' => 'boolean',
        'sort_order' => 'integer',
        'added_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectTask $projectTask) {
            $projectTask->added_by = Auth::id();

            if (blank($projectTask->code)) {
                $project = $projectTask->relationLoaded('project')
                    ? $projectTask->project
                    : Project::find($projectTask->project_id);

                $projectTask->code = self::generateTaskCode($project);
            }

            if (blank($projectTask->sort_order)) {
                $projectTask->sort_order = self::nextSortOrder(
                    (int) $projectTask->project_id,
                    $projectTask->project_sprint_id ? (int) $projectTask->project_sprint_id : null
                );
            }
        });

        static::updating(function (ProjectTask $projectTask) {
            $projectTask->updated_by = Auth::id();
        });

        static::saved(function (ProjectTask $projectTask) {
            $projectTask->projectSprint?->refreshDerivedTimeSeconds();
            $projectTask->projectSprint?->projectModule?->refreshTrackedTimeMetrics();
        });

        static::deleted(function (ProjectTask $projectTask) {
            $projectTask->projectSprint?->refreshDerivedTimeSeconds();
            $projectTask->projectSprint?->projectModule?->refreshTrackedTimeMetrics();
        });

        static::restored(function (ProjectTask $projectTask) {
            $projectTask->projectSprint?->refreshDerivedTimeSeconds();
            $projectTask->projectSprint?->projectModule?->refreshTrackedTimeMetrics();
        });
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

    public function projectModule()
    {
        return $this->belongsTo(ProjectModule::class);
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
        return $this->belongsTo(ProjectTaskStatus::class, 'status_id');
    }

    public function currentAssignee()
    {
        return $this->belongsTo(User::class, 'current_assignee_id');
    }

    public function assignmentLogs()
    {
        return $this->hasMany(ProjectTaskAssignmentLog::class)->latest('assigned_from');
    }

    public function currentAssignmentLog()
    {
        return $this->hasOne(ProjectTaskAssignmentLog::class)->where('is_current', true);
    }

    public function timeLogs()
    {
        return $this->hasMany(ProjectTaskTimeLog::class)->latest('started_at');
    }

    public function statusHistories()
    {
        return $this->hasMany(ProjectTaskStatusHistory::class)->orderBy('added_at', 'desc');
    }

    public function latestStatusHistory()
    {
        return $this->hasOne(ProjectTaskStatusHistory::class)->latestOfMany();
    }

    public function activeTimeLog()
    {
        return $this->hasOne(ProjectTaskTimeLog::class)->where('is_running', true);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'project_task_tags')
            ->withTimestamps();
    }

    public function tagLinks()
    {
        return $this->hasMany(ProjectTaskTag::class);
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

        return sprintf('%02d h : %02d m', $hours, $minutes);
    }
}

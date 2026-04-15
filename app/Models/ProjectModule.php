<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectModule extends Model
{
    use SoftDeletes, LogsModelActivity;

    protected $fillable = [
        'project_id',
        'name',
        'color',
        'description',
        'status_id',
        'owner_id',
        'start_date',
        'end_date',
        'completed_at',
        'estimated_time_seconds',
        'derived_time_seconds',
        'actual_time_seconds',
        'sort_order',
        'is_backlog',
        'is_system',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'status_id' => 'integer',
        'owner_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'completed_at' => 'datetime',
        'estimated_time_seconds' => 'integer',
        'derived_time_seconds' => 'integer',
        'actual_time_seconds' => 'integer',
        'sort_order' => 'integer',
        'is_backlog' => 'boolean',
        'is_system' => 'boolean',
        'added_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectModule $projectModule) {
            $projectModule->added_by = Auth::id();
        });

        static::updating(function (ProjectModule $projectModule) {
            $projectModule->updated_by = Auth::id();
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function status()
    {
        return $this->belongsTo(AgileModuleStatus::class, 'status_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function projectSprints()
    {
        return $this->hasMany(ProjectSprint::class)->orderForDisplay();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }

    public function scopeOrderForDisplay($query)
    {
        return $query
            ->orderByRaw('CASE WHEN is_backlog = 1 THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getEstimatedTimeFormattedAttribute()
    {
        $seconds = $this->estimated_time_seconds ?? 0;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
    }

    public function getEstimatedTimeMinutesAttribute()
    {
        return $this->estimated_time_seconds !== null
            ? (int) round($this->estimated_time_seconds / 60)
            : null;
    }

    public function getDerivedTimeFormattedAttribute(): string
    {
        return $this->formatSeconds($this->derived_time_seconds);
    }

    public function getActualTimeFormattedAttribute(): string
    {
        return $this->formatSeconds($this->actual_time_seconds);
    }

    public function getTaskCountAttribute(): int
    {
        if (
            !Schema::hasTable('tasks')
            || !Schema::hasColumn('tasks', 'project_sprint_id')
            || !Schema::hasTable('project_sprints')
        ) {
            return 0;
        }

        $query = DB::table('tasks')
            ->join('project_sprints', 'project_sprints.id', '=', 'tasks.project_sprint_id')
            ->where('project_sprints.project_module_id', $this->id);

        if (Schema::hasColumn('tasks', 'deleted_at')) {
            $query->whereNull('tasks.deleted_at');
        }

        if (Schema::hasColumn('project_sprints', 'deleted_at')) {
            $query->whereNull('project_sprints.deleted_at');
        }

        return (int) $query->count();
    }

    public function refreshTrackedTimeMetrics(): void
    {
        $derivedSeconds = $this->projectSprints()
            ->get(['estimated_time_seconds', 'derived_time_seconds'])
            ->sum(function (ProjectSprint $projectSprint) {
                $taskDerivedSeconds = (int) ($projectSprint->derived_time_seconds ?? 0);

                return $taskDerivedSeconds > 0
                    ? $taskDerivedSeconds
                    : (int) ($projectSprint->estimated_time_seconds ?? 0);
            });

        $actualSeconds = 0;

        if (
            Schema::hasTable('tasks')
            && Schema::hasColumn('tasks', 'project_sprint_id')
            && Schema::hasColumn('tasks', 'actual_time_seconds')
            && Schema::hasTable('project_sprints')
        ) {
            $query = DB::table('tasks')
                ->join('project_sprints', 'project_sprints.id', '=', 'tasks.project_sprint_id')
                ->where('project_sprints.project_module_id', $this->id);

            if (Schema::hasColumn('tasks', 'deleted_at')) {
                $query->whereNull('tasks.deleted_at');
            }

            if (Schema::hasColumn('project_sprints', 'deleted_at')) {
                $query->whereNull('project_sprints.deleted_at');
            }

            $actualSeconds = (int) $query->sum('tasks.actual_time_seconds');
        }

        $this->updateQuietly([
            'derived_time_seconds' => (int) $derivedSeconds,
            'actual_time_seconds' => (int) $actualSeconds,
        ]);
    }

    private function formatSeconds(?int $seconds): string
    {
        $totalSeconds = max(0, (int) ($seconds ?? 0));
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
    }

    /*----------------Activity Log Customization----------------*/

    // Never show these fields in activity log details.
    protected array $activityLogExceptAttributes = [
        'color',
        'description',
        'status_id',
        'completed_at',
        'derived_time_seconds',
        'actual_time_seconds',
        'sort_order',
        'added_by',
        'updated_by',
    ];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'owner_id' => 'Owner',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'estimated_time_seconds' => 'Estimated Time',
            'sort_order' => 'Sort Order',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'project_id' => $this->project?->name ?? $value,
            'owner_id' => User::find($value)?->name ?? $value,
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

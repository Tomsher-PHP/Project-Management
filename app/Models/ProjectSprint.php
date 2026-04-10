<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectSprint extends Model
{
    use SoftDeletes, LogsModelActivity;

    protected $fillable = [
        'project_id',
        'project_module_id',
        'name',
        'color',
        'description',
        'status_id',
        'start_date',
        'end_date',
        'estimated_time_seconds',
        'derived_time_seconds',
        'actual_time_seconds',
        'sort_order',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'project_module_id' => 'integer',
        'status_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'estimated_time_seconds' => 'integer',
        'derived_time_seconds' => 'integer',
        'actual_time_seconds' => 'integer',
        'sort_order' => 'integer',
        'added_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectSprint $projectSprint) {
            $projectSprint->added_by = Auth::id();
        });

        static::updating(function (ProjectSprint $projectSprint) {
            $projectSprint->updated_by = Auth::id();
        });

        static::saved(function (ProjectSprint $projectSprint) {
            $projectSprint->refreshDerivedTimeSeconds();
            $projectSprint->projectModule?->refreshTrackedTimeMetrics();
        });

        static::deleted(function (ProjectSprint $projectSprint) {
            $projectSprint->projectModule?->refreshTrackedTimeMetrics();
        });

        static::restored(function (ProjectSprint $projectSprint) {
            $projectSprint->refreshDerivedTimeSeconds();
            $projectSprint->projectModule?->refreshTrackedTimeMetrics();
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projectModule()
    {
        return $this->belongsTo(ProjectModule::class);
    }

    public function status()
    {
        return $this->belongsTo(AgileSprintStatus::class, 'status_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }

    public function getEstimatedTimeFormattedAttribute()
    {
        $seconds = $this->estimated_time_seconds ?? 0;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d h : %02d m', $hours, $minutes);
    }

    public function getEstimatedTimeMinutesAttribute()
    {
        return $this->estimated_time_seconds !== null
            ? (int) round($this->estimated_time_seconds / 60)
            : null;
    }

    public function getDerivedTimeFormattedAttribute(): string
    {
        $seconds = $this->derived_time_seconds ?? 0;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d h : %02d m', $hours, $minutes);
    }

    public function getActualTimeFormattedAttribute(): string
    {
        $seconds = $this->actual_time_seconds ?? 0;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d h : %02d m', $hours, $minutes);
    }

    public function getTaskCountAttribute(): int
    {
        if (!Schema::hasTable('tasks') || !Schema::hasColumn('tasks', 'project_sprint_id')) {
            return 0;
        }

        $query = DB::table('tasks')
            ->where('project_sprint_id', $this->id);

        if (Schema::hasColumn('tasks', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }

    public function refreshDerivedTimeSeconds(): void
    {
        $derivedSeconds = 0;
        $actualSeconds = 0;

        if (
            Schema::hasTable('tasks')
            && Schema::hasColumn('tasks', 'project_sprint_id')
        ) {
            $query = DB::table('tasks')
                ->where('project_sprint_id', $this->id);

            if (Schema::hasColumn('tasks', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if (Schema::hasColumn('tasks', 'estimated_time_seconds')) {
                $derivedSeconds = (int) $query->sum('estimated_time_seconds');
            }

            if (Schema::hasColumn('tasks', 'actual_time_seconds')) {
                $actualSeconds = (int) $query->sum('actual_time_seconds');
            }
        }

        $this->updateQuietly([
            'derived_time_seconds' => $derivedSeconds,
            'actual_time_seconds' => $actualSeconds,
        ]);
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

    // Skip creating activity log when only these fields change.
    protected array $activityLogIgnoredOnlyChanges = [
        'status_id',
        'derived_time_seconds',
        'actual_time_seconds',
        'completed_at',
    ];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'project_module_id' => 'Module',
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
            'project_module_id' => $this->projectModule?->name ?? $value,
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

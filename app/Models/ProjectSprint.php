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
        'estimated_time_seconds',
        'derived_time_sec',
        'sort_order',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'project_module_id' => 'integer',
        'estimated_time_seconds' => 'integer',
        'derived_time_sec' => 'integer',
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
            $projectSprint->refreshDerivedTimeSec();
            $projectSprint->projectModule?->refreshDerivedTimeSec();
        });

        static::deleted(function (ProjectSprint $projectSprint) {
            $projectSprint->projectModule?->refreshDerivedTimeSec();
        });

        static::restored(function (ProjectSprint $projectSprint) {
            $projectSprint->refreshDerivedTimeSec();
            $projectSprint->projectModule?->refreshDerivedTimeSec();
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

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
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
        $seconds = $this->derived_time_sec ?? 0;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d h : %02d m', $hours, $minutes);
    }

    public function getTaskCountAttribute(): int
    {
        if (!Schema::hasTable('project_tasks') || !Schema::hasColumn('project_tasks', 'project_sprint_id')) {
            return 0;
        }

        $query = DB::table('project_tasks')
            ->where('project_sprint_id', $this->id);

        if (Schema::hasColumn('project_tasks', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }

    public function refreshDerivedTimeSec(): void
    {
        $derivedSeconds = 0;

        if (
            Schema::hasTable('project_tasks')
            && Schema::hasColumn('project_tasks', 'project_sprint_id')
            && Schema::hasColumn('project_tasks', 'estimated_time_seconds')
        ) {
            $query = DB::table('project_tasks')
                ->where('project_sprint_id', $this->id);

            if (Schema::hasColumn('project_tasks', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            $derivedSeconds = (int) $query->sum('estimated_time_seconds');
        }

        $this->updateQuietly([
            'derived_time_sec' => $derivedSeconds,
        ]);
    }
}

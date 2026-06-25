<?php

namespace App\Models;

use App\Traits\ScheduleTaskFilterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TaskSchedule extends Model
{
    use SoftDeletes, ScheduleTaskFilterable;

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKDAYS = 'weekdays';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    protected $fillable = [
        'project_id',
        'project_milestone_id',
        'project_sprint_id',
        'task_type_id',
        'task_mode_id',
        'current_assignee_id',
        'name',
        'description',
        'priority',
        'estimated_time_seconds',
        'is_billable',
        'frequency_type',
        'start_date',
        'end_date',
        'week_days',
        'weekly_day',
        'month_days',
        'due_after_seconds',
        'last_generated_for',
        'last_generated_at',
        'is_active',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'estimated_time_seconds' => 'integer',
        'is_billable' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'week_days' => 'array',
        'weekly_day' => 'integer',
        'month_days' => 'array',
        'due_after_seconds' => 'integer',
        'is_active' => 'boolean',
        'last_generated_for' => 'date',
        'last_generated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TaskSchedule $taskSchedule) {
            $taskSchedule->added_by ??= Auth::id();
        });

        static::updating(function (TaskSchedule $taskSchedule) {
            $taskSchedule->updated_by = Auth::id();
        });
    }

    public function scopeAccessibleBy($query, User $user)
    {
        $query->whereHas('project', function ($q) use ($user) {
            $q->accessibleBy($user);
        });

        if ($user->is_super_admin || $user->can('task.view_all_tasks')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('current_assignee_id', $user->id)
                ->orWhereHas('currentAssignee', function ($q2) use ($user) {
                    $q2->accessibleBy($user);
                });
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function projectMilestone()
    {
        return $this->belongsTo(ProjectMilestone::class);
    }

    public function projectSprint()
    {
        return $this->belongsTo(ProjectSprint::class);
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class)->withTrashed();
    }

    public function taskMode()
    {
        return $this->belongsTo(TaskMode::class)->withTrashed();
    }

    public function currentAssignee()
    {
        return $this->belongsTo(User::class, 'current_assignee_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}

<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectTaskTimeLog extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'project_task_id',
        'user_id',
        'project_task_assignment_log_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'is_running',
        'note',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'project_task_id' => 'integer',
        'user_id' => 'integer',
        'project_task_assignment_log_id' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'is_running' => 'boolean',
        'added_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectTaskTimeLog $timeLog) {
            $timeLog->added_by = Auth::id();
        });

        static::updating(function (ProjectTaskTimeLog $timeLog) {
            $timeLog->updated_by = Auth::id();
        });
    }

    public function projectTask()
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignmentLog()
    {
        return $this->belongsTo(ProjectTaskAssignmentLog::class, 'project_task_assignment_log_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

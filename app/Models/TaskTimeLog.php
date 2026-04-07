<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TaskTimeLog extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'task_id',
        'user_id',
        'task_assignment_log_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'is_running',
        'note',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'task_id' => 'integer',
        'user_id' => 'integer',
        'task_assignment_log_id' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'is_running' => 'boolean',
        'added_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (TaskTimeLog $timeLog) {
            $timeLog->added_by = Auth::id();
        });

        static::updating(function (TaskTimeLog $timeLog) {
            $timeLog->updated_by = Auth::id();
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignmentLog()
    {
        return $this->belongsTo(TaskAssignmentLog::class, 'task_assignment_log_id');
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

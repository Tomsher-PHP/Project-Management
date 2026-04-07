<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TaskAssignmentLog extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'task_id',
        'user_id',
        'assigned_from',
        'assigned_to',
        'worked_time_seconds',
        'is_current',
        'handover_note',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'task_id' => 'integer',
        'user_id' => 'integer',
        'assigned_from' => 'datetime',
        'assigned_to' => 'datetime',
        'worked_time_seconds' => 'integer',
        'is_current' => 'boolean',
        'added_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (TaskAssignmentLog $assignmentLog) {
            $assignmentLog->added_by = Auth::id();
        });

        static::updating(function (TaskAssignmentLog $assignmentLog) {
            $assignmentLog->updated_by = Auth::id();
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

    public function timeLogs()
    {
        return $this->hasMany(TaskTimeLog::class)->latest('started_at');
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

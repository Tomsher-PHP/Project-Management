<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTimeLogChangeRequest extends Model
{
    protected $fillable = [
        'task_time_log_id',
        'user_id',
        'old_started_at',
        'old_ended_at',
        'new_started_at',
        'new_ended_at',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'old_started_at' => 'datetime',
        'old_ended_at' => 'datetime',
        'new_started_at' => 'datetime',
        'new_ended_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function timeLog()
    {
        return $this->belongsTo(TaskTimeLog::class, 'task_time_log_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getNewDurationAttribute(): ?int
    {
        if (!$this->new_started_at || !$this->new_ended_at) {
            return null;
        }

        return $this->new_started_at->diffInSeconds($this->new_ended_at);
    }
}

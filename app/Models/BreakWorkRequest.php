<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakWorkRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const PROCESSING_STATUS_PENDING = 'pending';
    public const PROCESSING_STATUS_PROCESSING = 'processing';
    public const PROCESSING_STATUS_COMPLETED = 'completed';
    public const PROCESSING_STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const PROCESSING_STATUSES = [
        self::PROCESSING_STATUS_PENDING,
        self::PROCESSING_STATUS_PROCESSING,
        self::PROCESSING_STATUS_COMPLETED,
        self::PROCESSING_STATUS_FAILED,
    ];

    protected $fillable = [
        'user_id',
        'work_date',
        'started_at',
        'ended_at',
        'duration_seconds',
        'description',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'processing_status',
        'task_id',
        'task_time_log_id',
        'processed_at',
        'process_error',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
        'processing_status' => 'string',
        'work_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function taskTimeLog()
    {
        return $this->belongsTo(TaskTimeLog::class);
    }
}

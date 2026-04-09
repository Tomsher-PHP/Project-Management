<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TaskStatusHistory extends Model
{
    protected $fillable = [
        'task_id',
        'status_id',
        'added_by',
        'added_at',
        'remarks',
    ];

    protected $casts = [
        'task_id' => 'integer',
        'status_id' => 'integer',
        'added_by' => 'integer',
        'added_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TaskStatusHistory $history) {
            $history->added_by = Auth::id();
            $history->added_at = now();
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

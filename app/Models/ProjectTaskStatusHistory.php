<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectTaskStatusHistory extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'project_task_id',
        'status_id',
        'added_by',
        'added_at',
        'remarks',
    ];

    protected $casts = [
        'project_task_id' => 'integer',
        'status_id' => 'integer',
        'added_by' => 'integer',
        'added_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectTaskStatusHistory $history) {
            $history->added_by = Auth::id();
            $history->added_at = now();
        });
    }

    public function projectTask()
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function status()
    {
        return $this->belongsTo(ProjectTaskStatus::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

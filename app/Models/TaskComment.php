<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'project_task_id',
        'user_id',
        'comment',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HandoffRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'project_milestone_id',
        'project_sprint_id',
        'source_task_id',
        'user_id',
        'purpose',
        'description',
        'status',
        'created_task_id',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'project_milestone_id' => 'integer',
            'project_sprint_id' => 'integer',
            'source_task_id' => 'integer',
            'user_id' => 'integer',
            'purpose' => 'string',
            'description' => 'string',
            'status' => 'integer',
            'created_task_id' => 'integer',
        ];
    }

    //Relations

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projectMilestone()
    {
        return $this->belongsTo(ProjectMilestone::class);
    }

    public function projectSprint()
    {
        return $this->belongsTo(ProjectSprint::class);
    }

    public function sourceTask()
    {
        return $this->belongsTo(Task::class, 'source_task_id');
    }

    public function createdTask()
    {
        return $this->belongsTo(Task::class, 'created_task_id');
    }

    public function handoffPurpose()
    {
        return $this->belongsTo(HandoffPurpose::class, 'purpose', 'name');
    }

    public function actions()
    {
        return $this->hasMany(HandoffRequestAction::class)->latestOf('created_at');
    }

    //Scopes

    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    public function scopeNoted($query)
    {
        return $query->where('status', 1);
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 2);
    }
}

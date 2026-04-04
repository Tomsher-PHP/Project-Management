<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;

class ProjectTaskTag extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'project_task_id',
        'tag_id',
    ];

    protected $casts = [
        'project_task_id' => 'integer',
        'tag_id' => 'integer',
    ];

    public function projectTask()
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}

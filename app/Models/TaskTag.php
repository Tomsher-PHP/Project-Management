<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;

class TaskTag extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'task_id',
        'tag_id',
    ];

    protected $casts = [
        'task_id' => 'integer',
        'tag_id' => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}

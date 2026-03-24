<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectComment extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'comment',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
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

<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;

class ProjectTechnology extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'project_id',
        'technology_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function technology()
    {
        return $this->belongsTo(Technology::class);
    }
}

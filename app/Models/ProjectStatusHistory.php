<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectStatusHistory extends Model
{
    protected $fillable = [
        'project_id',
        'from_status_id',
        'status_id',
        'added_by',
        'added_at',
        'remarks',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'added_by' => 'integer',
        'from_status_id' => 'integer',
        'project_id' => 'integer',
        'status_id' => 'integer',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            if (blank($model->added_by)) {
                $model->added_by = Auth::id();
            }

            if (blank($model->added_at)) {
                $model->added_at = now();
            }
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class)->withTrashed();
    }

    public function fromStatus()
    {
        return $this->belongsTo(ProjectStatus::class, 'from_status_id')->withTrashed();
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

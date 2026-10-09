<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectTimelineStatusHistory extends Model
{
    protected $fillable = [
        'project_timeline_id',
        'from_status',
        'status',
        'added_by',
        'added_at',
        'remarks',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'added_by' => 'integer',
        'from_status' => 'integer',
        'project_timeline_id' => 'integer',
        'status' => 'integer',
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

    public function projectTimeline()
    {
        return $this->belongsTo(ProjectTimeline::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

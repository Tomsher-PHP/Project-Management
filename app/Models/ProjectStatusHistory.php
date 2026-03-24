<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectStatusHistory extends Model
{
    protected $fillable = [
        'project_id',
        'status_id',
        'added_by',
        'added_at',
        'remarks',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'added_by' => 'integer',
        'project_id' => 'integer',
        'status_id' => 'integer',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->added_by = Auth::id();
            $model->added_at = now();
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

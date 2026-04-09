<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectStageHistory extends Model
{

    protected $fillable = [
        'project_id',
        'from_stage_id',
        'stage_id',
        'added_by',
        'added_at',
        'remarks',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'from_stage_id' => 'integer',
        'stage_id' => 'integer',
        'added_by' => 'integer',
        'added_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectStageHistory $history) {
            if (blank($history->added_by)) {
                $history->added_by = Auth::id();
            }

            if (blank($history->added_at)) {
                $history->added_at = now();
            }
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function stage()
    {
        return $this->belongsTo(ProjectStage::class)->withTrashed();
    }

    public function fromStage()
    {
        return $this->belongsTo(ProjectStage::class, 'from_stage_id')->withTrashed();
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

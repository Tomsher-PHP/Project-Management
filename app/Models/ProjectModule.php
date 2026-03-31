<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ProjectModule extends Model
{
    use SoftDeletes, LogsModelActivity;

    protected $fillable = [
        'project_id',
        'name',
        'color',
        'description',
        'estimated_time_seconds',
        'order',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'estimated_time_seconds' => 'integer',
        'order' => 'integer',
        'added_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectModule $projectModule) {
            $projectModule->added_by = Auth::id();
        });

        static::updating(function (ProjectModule $projectModule) {
            $projectModule->updated_by = Auth::id();
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getEstimatedTimeFormattedAttribute()
    {
        $seconds = $this->estimated_time_seconds ?? 0;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d h : %02d m', $hours, $minutes);
    }

    public function getEstimatedTimeMinutesAttribute()
    {
        return $this->estimated_time_seconds !== null
            ? (int) round($this->estimated_time_seconds / 60)
            : null;
    }
}

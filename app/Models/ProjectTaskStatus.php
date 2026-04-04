<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ProjectTaskStatus extends Model
{
    use SoftDeletes, Filterable, Sortable, LogsModelActivity;

    protected $fillable = [
        'name',
        'flow_type',
        'code',
        'color',
        'type',
        'sort_order',
        'is_default',
        'is_completed',
        'is_active',
        'is_system',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'name',
        'flow_type',
        'code',
        'type',
        'sort_order',
    ];

    protected $searchable = [
        'name',
        'flow_type',
        'code',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'flow_type' => 'string',
            'code' => 'string',
            'color' => 'string',
            'type' => 'string',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_completed' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProjectTaskStatus $projectTaskStatus) {
            $projectTaskStatus->added_by = Auth::id();
        });

        static::updating(function (ProjectTaskStatus $projectTaskStatus) {
            $projectTaskStatus->updated_by = Auth::id();
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFlow($query, string $flowType)
    {
        return $query->where('flow_type', $flowType);
    }

    public function projectTasks()
    {
        return $this->hasMany(ProjectTask::class, 'status_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(ProjectTaskStatusHistory::class, 'status_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

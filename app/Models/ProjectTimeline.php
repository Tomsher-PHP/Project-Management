<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTimeline extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PLANNED = 1;
    public const STATUS_ACTIVE = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_CANCELLED = 4;

    public const DEFAULT_TYPE = 'new';

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'status',
        'start_date',
        'end_date',
        'customer_end_date',
        'estimated_time_seconds',
        'customer_estimate_seconds',
        'sort_order',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'customer_end_date' => 'date',
        'estimated_time_seconds' => 'integer',
        'customer_estimate_seconds' => 'integer',
        'status' => 'integer',
        'sort_order' => 'integer',
        'created_by' => 'integer',
        'project_id' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}

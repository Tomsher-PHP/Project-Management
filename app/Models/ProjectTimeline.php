<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTimeline extends Model
{
    use HasFactory, SoftDeletes, LogsModelActivity;

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
        'actual_time_seconds',
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
        'actual_time_seconds' => 'integer',
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

    public function statusHistories()
    {
        return $this->hasMany(ProjectTimelineStatusHistory::class);
    }

    public function notificationLogs()
    {
        return $this->morphMany(ProjectNotificationLog::class, 'link');
    }

    /*----------------Activity Log Customization----------------*/

    // Never show these fields in activity log details.
    protected array $activityLogExceptAttributes = [
        'created_by',
        'actual_time_seconds',
        'sort_order',
        'notes',
    ];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'project_id' => 'Project',
            'name' => 'Name',
            'type' => 'Type',
            'status' => 'Status',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'customer_end_date' => 'Customer End Date',
            'estimated_time_seconds' => 'Estimated Time',
            'customer_estimate_seconds' => 'Customer Estimate Time',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'project_id' => $this->project?->name ?? Project::find($value)?->name ?? $value,
            'type' => config('project_constants.project_timeline_types.' . $value, ucfirst((string) $value)),
            'status' => ucfirst(config('project_constants.project_timeline_statuses.' . $value, (string) $value)),
            'estimated_time_seconds' => $this->secondsToReadable($value),
            'customer_estimate_seconds' => $this->secondsToReadable($value),
            default => $value,
        };
    }
}

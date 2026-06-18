<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    public const PROJECT_ASSIGNED = 'project_assigned';
    public const PROJECT_STATUS_CHANGED = 'project_status_changed';
    public const PROJECT_STAGE_CHANGED = 'project_stage_changed';
    public const PROJECT_TIMELINE_CHANGED = 'project_timeline_changed';
    
    public const TASK_ASSIGNED = 'task_assigned';
    public const TASK_STATUS_CHANGED = 'task_status_changed';
    public const TASK_TIMELINE_CHANGED = 'task_timeline_changed';
    
    public const SHIFT_SCHEDULED = 'shift_scheduled';
    public const TEAM_ASSIGNED = 'team_assigned';
    
    public const TASK_REQUEST = 'task_request';
    public const TASK_LOG_REQUEST = 'task_log_request';
    public const HANDOFF_REQUEST = 'handoff_request';
    public const BREAK_REQUEST = 'break_request';
    public const TASK_TIME_EXTEND_REQUEST = 'task_time_extension_request';

    protected $fillable = [
        'user_id',
        'action',
        'in_app',
        'mail',
    ];

    protected $casts = [
        'in_app' => 'boolean',
        'mail' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

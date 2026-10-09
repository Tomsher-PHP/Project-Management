<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_notification_setting_id',
        'project_timeline_id',
        'scheduled_for',
        'sent_at',
        'status',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
        'sent_at' => 'datetime',
    ];

    public function projectNotificationSetting()
    {
        return $this->belongsTo(ProjectNotificationSetting::class);
    }

    public function projectTimeline()
    {
        return $this->belongsTo(ProjectTimeline::class);
    }
}

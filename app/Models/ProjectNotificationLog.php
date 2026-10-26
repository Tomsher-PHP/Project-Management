<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectNotificationLog extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'project_notification_setting_id',
        'link_id',
        'link_type',
        'scheduled_for',
        'reminder_occasion',
        'sent_at',
        'status',
    ];

    protected $casts = [
        'link_id' => 'integer',
        'scheduled_for' => 'date',
        'sent_at' => 'datetime',
    ];

    public function projectNotificationSetting()
    {
        return $this->belongsTo(ProjectNotificationSetting::class);
    }

    public function link()
    {
        return $this->morphTo(__FUNCTION__, 'link_type', 'link_id');
    }
}

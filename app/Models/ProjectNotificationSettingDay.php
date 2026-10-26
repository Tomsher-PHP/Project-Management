<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectNotificationSettingDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_notification_setting_id',
        'days_before',
    ];

    protected $casts = [
        'project_notification_setting_id' => 'integer',
        'days_before' => 'integer',
    ];

    public function projectNotificationSetting()
    {
        return $this->belongsTo(ProjectNotificationSetting::class, 'project_notification_setting_id');
    }
}

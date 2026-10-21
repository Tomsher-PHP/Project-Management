<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectNotificationSetting extends Model
{
    use HasFactory, SoftDeletes;

    public const NOTIFICATION_TYPE_TIMELINE_ENDING_SOON = 'timeline_ending_soon';

    public const NOTIFICATION_TYPE_HOSTING_ENDING_SOON = 'hosting_ending_soon';

    protected $fillable = [
        'notification_type',
        'is_enabled',
        'created_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function days()
    {
        return $this->hasMany(ProjectNotificationSettingDay::class, 'project_notification_setting_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_notification_setting_users', 'project_notification_setting_id', 'user_id')
            ->withPivot(['id', 'email'])
            ->withTimestamps();
    }

    public function recipients()
    {
        return $this->hasMany(ProjectNotificationSettingUser::class, 'project_notification_setting_id');
    }

    public function emailRecipients()
    {
        return $this->hasMany(ProjectNotificationSettingUser::class, 'project_notification_setting_id')
            ->whereNotNull('email');
    }
}


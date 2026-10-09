<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectNotificationSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'notification_type',
        'is_enabled',
        'days_before',
        'created_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'days_before' => 'integer',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_notification_setting_users', 'project_notification_setting_id', 'user_id')->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNotificationSettingUser extends Model
{
    use HasFactory;

    protected $table = 'project_notification_setting_users';

    protected $fillable = [
        'project_notification_setting_id',
        'user_id',
        'email',
    ];

    protected $casts = [
        'project_notification_setting_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(ProjectNotificationSetting::class, 'project_notification_setting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to only user recipients.
     */
    public function scopeOnlyUsers($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope to only email recipients.
     */
    public function scopeOnlyEmails($query)
    {
        return $query->whereNotNull('email');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{

    public const DAILY_WORK_HOURS_WARNING_MAIL = 'daily_work_hours_warning_mail';
    public const DAILY_WORK_REPORT_MAIL = 'daily_work_report_mail';

    public const USER_SETTINGS_KEYS = [
        self::DAILY_WORK_HOURS_WARNING_MAIL,
        self::DAILY_WORK_REPORT_MAIL,
    ];

    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

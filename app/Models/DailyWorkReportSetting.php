<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyWorkReportSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'report_name',
        'report_key',
        'enabled',
        'frequency',
        'delivery_time',
        'timezone',
        'reporting_period',
        'send_zero_activity',
        'include_inactive_users',
        'report_sections',
        'settings',
        'weekly_day',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'send_zero_activity' => 'boolean',
            'include_inactive_users' => 'boolean',
            'report_sections' => 'array',
            'settings' => 'array',
            'weekly_day' => 'integer',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DailyWorkReportRecipient::class, 'report_setting_id');
    }

    public function reportLogs(): HasMany
    {
        return $this->hasMany(DailyWorkReportLog::class, 'report_setting_id');
    }

    public static function forReport(string $reportKey): self
    {
        return static::firstOrCreate(
            ['report_key' => $reportKey],
            [
                'setting_key' => $reportKey,
                'enabled' => false,
                'frequency' => 'daily',
                'delivery_time' => '00:00:00',
                'timezone' => 'Asia/Dubai',
                'reporting_period' => 'previous_day',
                'send_zero_activity' => false,
                'include_inactive_users' => false,
                'report_sections' => [
                    'current_tasks',
                    'daily_activity',
                    'time_logs',
                    'comments',
                    'developer_notes',
                    'deadlines',
                ],
                'weekly_day' => 1,
            ]
        );
    }

    public static function current(): self
    {
        return static::forReport('daily_work');
    }
}

<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyWorkReportLog extends Model
{
    protected $fillable = [
        'report_setting_id',
        'user_id',
        'report_date',
        'period_start',
        'period_end',
        'report_type',
        'status',
        'report_data',
        'recipients_snapshot',
        'attempt_count',
        'last_error',
        'generated_at',
        'completed_at',
        'report_key',
        'report_variant',
    ];

    protected function casts(): array
    {
        return [
            'report_setting_id' => 'integer',
            'report_date' => 'date',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'report_data' => 'array',
            'recipients_snapshot' => 'array',
            'attempt_count' => 'integer',
            'generated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function reportSetting(): BelongsTo
    {
        return $this->belongsTo(DailyWorkReportSetting::class, 'report_setting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DailyWorkReportDelivery::class);
    }

    public function setGeneratedAtAttribute($value): void
    {
        $this->attributes['generated_at'] = $this->normalizeToAppTimezone($value);
    }

    public function setCompletedAtAttribute($value): void
    {
        $this->attributes['completed_at'] = $this->normalizeToAppTimezone($value);
    }

    protected function normalizeToAppTimezone($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $appTimezone = config('app.timezone', 'UTC');

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->setTimezone($appTimezone)->format('Y-m-d H:i:s');
        }

        return Carbon::parse($value, $appTimezone)->setTimezone($appTimezone)->format('Y-m-d H:i:s');
    }
}

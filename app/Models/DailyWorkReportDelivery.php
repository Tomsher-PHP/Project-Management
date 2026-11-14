<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyWorkReportDelivery extends Model
{
    protected $fillable = [
        'daily_work_report_log_id',
        'recipient_email',
        'recipient_name',
        'recipient_type',
        'recipient_sources',
        'status',
        'attempt_count',
        'sent_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'recipient_sources' => 'array',
            'attempt_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function reportLog(): BelongsTo
    {
        return $this->belongsTo(
            DailyWorkReportLog::class,
            'daily_work_report_log_id'
        );
    }
}
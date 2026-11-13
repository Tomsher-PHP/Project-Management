<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyWorkReportLog extends Model
{
    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DailyWorkReportDelivery::class);
    }
}
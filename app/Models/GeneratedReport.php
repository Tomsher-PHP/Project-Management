<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedReport extends Model
{
    public const REPORT_TYPE_TIME_TRACKING = 'time_tracking';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ORPHANED = 'orphaned';

    public const REQUESTED_VIA_MANUAL = 'manual';
    public const REQUESTED_VIA_SCHEDULED = 'scheduled';

    protected $fillable = [
        'user_id',
        'report_type',
        'status',
        'requested_via',
        'filters',
        'disk',
        'path',
        'filename',
        'requested_at',
        'processing_started_at',
        'generated_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'filters' => 'array',
        'requested_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'generated_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}

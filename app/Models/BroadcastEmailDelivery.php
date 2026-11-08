<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastEmailDelivery extends Model
{
    public const STATUS_PENDING = 1;
    public const STATUS_PROCESSING = 2;
    public const STATUS_SENT = 3;
    public const STATUS_FAILED = 4;

    protected $fillable = [
        'broadcast_id',
        'user_id',
        'email',
        'status',
        'attempts',
        'sent_at',
        'last_attempt_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'broadcast_id' => 'integer',
            'user_id' => 'integer',
            'status' => 'integer',
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'last_attempt_at' => 'datetime',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SENT => 'Sent',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


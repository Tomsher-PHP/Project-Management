<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskExtendTimeRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'task_id',
        'user_id',
        'estimated_time_seconds',
        'new_estimated_time_seconds',
        'user_requested_time_seconds',
        'status',
        'reason',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getEstimatedTimeFormattedAttribute(): string
    {
        return $this->formatSeconds($this->estimated_time_seconds);
    }

    public function getNewEstimatedTimeFormattedAttribute(): string
    {
        return $this->formatSeconds($this->new_estimated_time_seconds);
    }

    public function getUserRequestedTimeFormattedAttribute(): string
    {
        return $this->formatSeconds(
            $this->user_requested_time_seconds
                ?? $this->new_estimated_time_seconds
        );
    }

    public function hasDifference(): bool
    {
        return $this->user_requested_time_seconds !== null
            && (int) $this->user_requested_time_seconds !== (int) $this->new_estimated_time_seconds;
    }

    private function formatSeconds(?int $seconds): string
    {
        $totalSeconds = max(0, (int) ($seconds ?? 0));
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
    }
}

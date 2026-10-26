<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveChangeRequest extends Model
{
    protected $fillable = [
        'leave_request_id',
        'requested_by',
        'assigned_to',

        'old_leave_type_id',
        'old_from_date',
        'old_to_date',
        'old_duration',
        'old_day_type',
        'old_half_day_type',

        'new_leave_type_id',
        'new_from_date',
        'new_to_date',
        'new_duration',
        'new_day_type',
        'new_half_day_type',

        'reason',

        'status',

        'approved_by',
        'approved_at',

        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'assigned_to' => 'array',
        'old_from_date' => 'date',
        'old_to_date' => 'date',
        'new_from_date' => 'date',
        'new_to_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function oldLeaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'old_leave_type_id');
    }

    public function newLeaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'new_leave_type_id');
    }
}

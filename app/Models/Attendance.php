<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_date',
        'status',
        'leave_source',
        'leave_request_id',
        'check_in',
        'check_out',
        'working_hours',
        'remarks',
        'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'working_hours' => 'decimal:2',
    ];

    /**
     * Employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User who marked attendance.
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'marked_by'
        );
    }

    /**
     * Related leave request.
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(
            LeaveRequest::class
        );
    }
}

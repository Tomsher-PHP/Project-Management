<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'assigned_to',

        /*
         * Leave type / day type.
         */
        'type',
        'half_day_type',

        /*
         * Requested information.
         */
        'requested_from_date',
        'requested_to_date',
        'duration',

        'reason',
        'attachment',

        /*
         * Status.
         */
        'status',

        /*
         * Approval information.
         */
        'approver_comment',
        'approved_by',
        'approved_from_date',
        'approved_to_date',
        'approved_duration',
        'approved_at',

        /*
         * Rejection information.
         */
        'rejected_by',
        'rejected_at',

        /*
         * Cancellation information.
         */
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',

        /*
         * Submission information.
         */
        'submitted_at',

        /*
         * Other fields.
         */
        'sort_order',
        'is_system',
        'is_active',

        /*
         * Paid / unpaid allocation.
         */
        'paid_days',
        'unpaid_days',

        /*
         * User who created/added the request.
         */
        'added_by',
    ];

    protected $casts = [

        /*
         * Multiple approvers.
         */
        'assigned_to' => 'array',

        /*
         * Requested dates.
         */
        'requested_from_date' => 'date',
        'requested_to_date' => 'date',

        /*
         * Approved dates.
         */
        'approved_from_date' => 'date',
        'approved_to_date' => 'date',

        /*
         * Date/time fields.
         */
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',

        /*
         * Duration fields.
         */
        'duration' => 'decimal:2',
        'approved_duration' => 'decimal:2',

        /*
         * Paid / unpaid leave allocation.
         */
        'paid_days' => 'decimal:2',
        'unpaid_days' => 'decimal:2',

        /*
         * Boolean fields.
         */
        'is_system' => 'boolean',
        'is_active' => 'boolean',

        /*
         * Half-day type.
         */
        'half_day_type' => 'string',

        /*
         * User who added the request.
         */
        'added_by' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Employee for whom the leave request was created.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * Leave type.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(
            LeaveType::class,
            'leave_type_id'
        );
    }

    /**
     * User who approved the leave request.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    /**
     * User who rejected the leave request.
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'rejected_by'
        );
    }

    /**
     * User who cancelled the leave request.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    /**
     * User who created/added the leave request.
     *
     * This is particularly important for requests created
     * through "Mark Attendance".
     *
     * Example:
     *
     * user_id  = employee
     * added_by = manager/admin who created it
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'added_by'
        );
    }

    /**
     * Multiple users who can approve this leave request.
     */
    public function approvers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'leave_request_approvers',
            'leave_request_id',
            'approver_id'
        )
        ->withPivot([
            'sort_order',
            'status',
            'acted_at',
        ])
        ->withTimestamps();
    }

    /**
     * Attendance records associated with this leave request.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(
            Attendance::class,
            'leave_request_id'
        );
    }

    /**
     * History records for this leave request.
     *
     * Used to track:
     *
     * - created
     * - updated
     * - approved
     * - rejected
     * - cancelled
     * - date changes
     * - other leave changes
     */
    public function histories(): HasMany
    {
        return $this->hasMany(
            LeaveRequestHistory::class,
            'leave_request_id'
        )->latest();
    }
}

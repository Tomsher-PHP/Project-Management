<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'valid_from',
        'valid_to',
        'yearly_entitlement',
        'monthly_entitlement',
        'opening_balance',
        'current_balance',
        'used_balance',
        'paid_days_used',
        'unpaid_days_used',
        'cancelled_days_restored',
        'carry_forward_balance',
        'is_carry_forward',
        'created_by',
        'updated_by',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',

        'yearly_entitlement' => 'decimal:2',
        'monthly_entitlement' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'used_balance' => 'decimal:2',
        'paid_days_used' => 'decimal:2',
        'unpaid_days_used' => 'decimal:2',
        'cancelled_days_restored' => 'decimal:2',
        'carry_forward_balance' => 'decimal:2',

        'valid_from' => 'date',
        'valid_to' => 'date',

        'is_carry_forward' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * User who owns this leave balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * Leave type associated with this balance.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(
            LeaveType::class,
            'leave_type_id'
        );
    }

    /**
     * User who created the balance.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * User who last updated the balance.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    /**
     * Leave transactions belonging to this user and leave type.
     *
     * The transaction table is linked using user_id and
     * leave_type_id rather than directly using balance_id.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(
            LeaveTransaction::class,
            'user_id',
            'user_id'
        )->where(
            'leave_type_id',
            $this->leave_type_id
        );
    }
}

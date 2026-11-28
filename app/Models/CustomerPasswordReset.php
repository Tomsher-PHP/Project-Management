<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPasswordReset extends Model
{
    protected $table = 'customer_password_resets';

    protected $fillable = [
        'customer_id',
        'email',
        'challenge_token_hash',
        'otp_hash',
        'attempt_count',
        'expires_at',
        'verified_at',
        'reset_token_hash',
        'reset_expires_at',
        'used_at',
        'invalidated_at',
    ];

    protected $hidden = [
        'otp_hash',
        'challenge_token_hash',
        'reset_token_hash',
    ];

    protected $casts = [
        'attempt_count' => 'integer',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'reset_expires_at' => 'datetime',
        'used_at' => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

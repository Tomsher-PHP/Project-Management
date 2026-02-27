<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserShift extends Model
{
    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'break_duration',
        'effective_from',
        'effective_to',
        'added_by',
        'updated_by',
    ];

    public static function booted()
    {
        static::creating(function ($shift) {
            $shift->added_by = Auth::id() ?? null;
        });

        static::updating(function ($shift) {
            $shift->updated_by = Auth::id() ?? null;
        });
    }
}

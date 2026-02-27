<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserWorkingDay extends Model
{
    protected $fillable = [
        'user_id',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
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

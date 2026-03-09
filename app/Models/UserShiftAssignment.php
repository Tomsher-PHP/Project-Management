<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserShiftAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'shift_name',
        'time_from',
        'time_to',
        'break_duration',
        'color_code',
        'date_from',
        'date_to',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'shift_id' => 'integer',
        ];
    }
}

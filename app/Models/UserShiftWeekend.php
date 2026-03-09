<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserShiftWeekend extends Model
{
    protected $fillable = [
        'user_shift_assignment_id',
        'weekday',
        'week_number'
    ];

    protected function casts(): array
    {
        return [
            'user_shift_assignment_id' => 'integer',
            'weekday' => 'integer',
            'week_number' => 'integer',
        ];
    }
}

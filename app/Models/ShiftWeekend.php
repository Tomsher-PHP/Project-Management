<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftWeekend extends Model
{
    protected $fillable = [
        'shift_id',
        'weekday',
        'week_number'
    ];

    protected function casts(): array
    {
        return [
            'shift_id' => 'integer',
            'weekday' => 'integer',
            'week_number' => 'integer',
        ];
    }
}

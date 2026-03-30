<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;

class ShiftWeekend extends Model
{
    use LogsModelActivity;

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

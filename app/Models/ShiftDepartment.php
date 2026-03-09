<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftDepartment extends Model
{
    protected $fillable = [
        'shift_id',
        'department_id',
    ];

    protected function casts(): array
    {
        return [
            'shift_id' => 'integer',
            'department_id' => 'integer',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HandoffPurpose extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_system',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

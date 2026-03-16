<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Filterable;

class Department extends Model
{
    use SoftDeletes, Filterable;

    protected $fillable = [
        'name',
        'order',
        'default',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'order' => 'integer',
            'default' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}

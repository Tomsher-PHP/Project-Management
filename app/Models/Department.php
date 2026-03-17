<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Filterable;
use App\Traits\Sortable;

class Department extends Model
{
    use SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'name',
        'order',
        'default',
        'status'
    ];

    protected $sortable = [
        'name',
        'order',
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

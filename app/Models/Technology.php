<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technology extends Model
{
    use SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'name',
        'order',
        'default',
        'status',
    ];

    protected $sortable = [
        'name',
        'order',
    ];

    protected $casts = [
        'name' => 'string',
        'order' => 'integer',
        'default' => 'boolean',
        'status' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}

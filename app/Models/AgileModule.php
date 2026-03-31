<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgileModule extends Model
{
    use SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'name',
        'color',
        'description',
        'order',
        'default',
        'status',
    ];

    protected $sortable = [
        'name',
        'order',
    ];

    protected $searchable = [
        'name',
        'description',
        'color',
    ];

    protected $casts = [
        'name' => 'string',
        'color' => 'string',
        'description' => 'string',
        'order' => 'integer',
        'default' => 'boolean',
        'status' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}

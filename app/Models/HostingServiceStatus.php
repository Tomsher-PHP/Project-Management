<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingServiceStatus extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',
        'is_active',
        'is_system',
        'is_default',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

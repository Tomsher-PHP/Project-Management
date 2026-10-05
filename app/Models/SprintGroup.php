<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SprintGroup extends Model
{
    use SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'name',
        'color',
        'sort_order',
        'is_system',
        'is_active',
    ];

    protected $sortable = [
        'name',
        'sort_order',
    ];

    protected $searchable = [
        'name',
        'color',
    ];

    protected $casts = [
        'name' => 'string',
        'color' => 'string',
        'sort_order' => 'integer',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function agileSprints()
    {
        return $this->hasMany(AgileSprint::class);
    }
}

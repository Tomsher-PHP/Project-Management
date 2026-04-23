<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Industry extends Model
{
    use SoftDeletes, Filterable, Sortable, LogsModelActivity, HasFormOptions;

    protected $fillable = [
        'name',
        'parent_id',
        'sort_order',
        'is_system',
        'is_active'
    ];

    protected $sortable = [
        'name',
        'sort_order',
    ];

    protected $searchable = ['name'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }


    public function parent()
    {
        return $this->belongsTo(Industry::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Industry extends Model
{
    use SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'name',
        'parent_id',
        'order',
        'default',
        'status'
    ];

    protected $sortable = [
        'name',
        'order',
    ];

    protected $searchable = ['name'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'parent_id' => 'integer',
            'order' => 'integer',
            'default' => 'boolean',
            'status' => 'boolean',
        ];
    }


    public function parent()
    {
        return $this->belongsTo(Industry::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}

<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulletinCategory extends Model
{
    use Filterable, Sortable, SoftDeletes;

    protected $table = 'bulletin_categories';

    protected $fillable = [
        'name',
        'color',
        'sort_order',
        'is_default',
        'is_system',
        'is_active',
    ];

    protected $sortable = [
        'name',
        'color',
        'sort_order',
        'is_default',
        'is_active',
    ];

    protected $searchable = ['name'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'color' => 'string',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

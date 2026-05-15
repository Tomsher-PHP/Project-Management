<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectStatus extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    public const TYPE_OPEN = 'open';
    public const TYPE_IN_PROGRESS = 'in_progress';
    public const TYPE_COMPLETED = 'completed';
    public const TYPE_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'code',
        'color',
        'type',
        'sort_order',
        'is_default',
        'is_completed',
        'is_system',
        'is_active'
    ];

    protected $sortable = [
        'name',
        'code',
        'type',
        'sort_order',
    ];

    protected $searchable = ['name', 'code', 'type'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'code' => 'string',
            'color' => 'string',
            'type' => 'string',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_completed' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

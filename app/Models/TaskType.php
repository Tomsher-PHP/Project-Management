<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskType extends Model
{
    use Filterable, Sortable, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'color',
        'sort_order',
        'is_default',
        'is_system',
        'is_active',
    ];

    protected $sortable = [
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected $searchable = [
        'name',
        'code',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'task_type', 'code');
    }
}

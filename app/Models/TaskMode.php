<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskMode extends Model
{
    use Filterable, Sortable, SoftDeletes, HasFormOptions;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'is_productive',
        'is_rework',
        'track_performance',
        'customer_request',
        'sort_order',
        'is_default',
        'is_system',
        'is_active',
    ];

    protected $sortable = [
        'name',
        'code',
        'is_active',
        'is_default',
    ];

    protected $searchable = [
        'name',
        'code',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_rework' => 'boolean',
            'is_productive' => 'boolean',
            'track_performance' => 'boolean',
            'customer_request' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'task_mode', 'code');
    }
}

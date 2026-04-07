<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;

class TaskMode extends Model
{
    use Filterable, Sortable;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'is_rework',
        'is_productive',
        'affects_performance',
        'affects_billable_time',
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
            'is_rework' => 'boolean',
            'is_productive' => 'boolean',
            'affects_performance' => 'boolean',
            'affects_billable_time' => 'boolean',
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

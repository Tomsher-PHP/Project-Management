<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceType extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    protected $fillable = [
        'service_group_id',
        'name',
        'color',
        'code',
        'description',
        'is_active',
        'sort_order',
        'is_default',
        'is_system',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected $searchable = ['name', 'code'];

    protected function casts(): array
    {
        return [
            'service_group_id' => 'integer',
            'name' => 'string',
            'color' => 'string',
            'code' => 'string',
            'description' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_system' => 'boolean',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function serviceGroup(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class);
    }

    public function projects()
    {
        return $this->belongsToMany(
            Project::class,
            'project_service_types'
        )->withTimestamps();
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

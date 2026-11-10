<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected static function booted(): void
    {
        static::creating(function (ServiceType $serviceType) {
            if (empty($serviceType->code) || static::withTrashed()->where('code', $serviceType->code)->exists()) {
                $serviceType->code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique 6-digit numeric code (e.g. 123456).
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

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

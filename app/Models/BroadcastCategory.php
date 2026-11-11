<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BroadcastCategory extends Model
{
    use Filterable, Sortable, SoftDeletes;

    protected $table = 'broadcast_categories';

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
        'color',
        'sort_order',
        'is_default',
        'is_active',
    ];

    protected $searchable = ['name', 'code'];

    protected static function booted(): void
    {
        static::creating(function (BroadcastCategory $category) {
            if (empty($category->code) || static::withTrashed()->where('code', $category->code)->exists()) {
                $category->code = static::generateUniqueCode();
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
            'name' => 'string',
            'code' => 'string',
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


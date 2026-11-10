<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectCategory extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    protected const CODE_PREFIX = 'pct';

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_system',
        'is_active'
    ];

    protected $sortable = [
        'name',
        'code',
        'sort_order',
    ];

    protected $searchable = ['name', 'code'];

    protected static function booted(): void
    {
        static::creating(function (ProjectCategory $category) {
            if (empty($category->code) || static::withTrashed()->where('code', $category->code)->exists()) {
                $category->code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique code using CODE_PREFIX, a hyphen, and 6 digits (e.g. pct-123456).
     */
    public static function generateUniqueCode(): string
    {
        do {
            $numbers = (string) random_int(100000, 999999);
            $code = static::CODE_PREFIX . '-' . $numbers;
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'code' => 'string',
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

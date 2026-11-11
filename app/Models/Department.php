<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;

class Department extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

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
        static::creating(function (Department $department) {
            if (empty($department->code) || static::withTrashed()->where('code', $department->code)->exists()) {
                $department->code = static::generateUniqueCode();
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

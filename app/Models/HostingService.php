<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingService extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',
        'is_active',
        'is_system',
        'is_default',
    ];

    protected $sortable = [
        'name',
        'code',
        'sort_order',
    ];

    protected $searchable = ['name', 'code', 'description'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'code' => 'string',
            'description' => 'string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}

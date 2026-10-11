<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceGroup extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    protected $fillable = [
        'code',
        'name',
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

    protected $searchable = ['name', 'code'];

    protected function casts(): array
    {
        return [
            'code' => 'string',
            'name' => 'string',
            'description' => 'string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public const DEVELOPMENT = 'development';
    public const MAINTENANCE = 'maintenance';
    public const SEO = 'seo';
    public const SMO = 'smo';
    public const SUPPORT = 'support';
    public const OTHER = 'other';

    public static function getPredefinedGroups(): array
    {
        return [
            self::DEVELOPMENT => 'Development',
            self::MAINTENANCE => 'Maintenance',
            self::SEO => 'SEO',
            self::SMO => 'SMO',
            self::SUPPORT => 'Support',
            self::OTHER => 'Other',
        ];
    }

    public static function isValidCode(string $code): bool
    {
        return array_key_exists($code, self::getPredefinedGroups());
    }

    public static function getByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

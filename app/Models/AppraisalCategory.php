<?php

namespace App\Models;


use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalCategory extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    protected $fillable = [
        'name',
        'sort_order',
        'is_system',
        'is_default',
        'is_active'
    ];

    protected $sortable = [
        'name',
        'sort_order',
    ];

    protected $searchable = ['name'];

    protected static function booted(): void
    {
        static::creating(function (AppraisalCategory $category) {
            if (empty($category->code) || static::withTrashed()->where('code', $category->code)->exists()) {
                $category->code = static::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = 'APC-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    protected function casts(): array
    {
        return [
            'code' => 'string',
            'name' => 'string',
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function questions()
    {
        return $this->hasMany(AppraisalQuestion::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

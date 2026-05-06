<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Kpi extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'is_system',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'name',
        'is_active',
    ];

    protected $searchable = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'slug' => 'string',
            'description' => 'string',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Kpi $kpi) {
            $kpi->slug = static::generateUniqueSlug($kpi->name);
            $kpi->added_by = Auth::id();
        });

        static::updating(function (Kpi $kpi) {
            if ($kpi->isDirty('name')) {
                $kpi->slug = static::generateUniqueSlug($kpi->name, $kpi->id);
            }

            $kpi->updated_by = Auth::id();
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : Str::random(8);
        $originalSlug = $slug;
        $counter = 2;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}

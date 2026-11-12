<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class MeetingType extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    protected $fillable = [
        'name',
        'code',
        'color',
        'description',
        'sort_order',
        'is_default',
        'is_active',
        'is_system',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'name',
        'code',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $searchable = [
        'name',
        'code',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'code' => 'string',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MeetingType $model) {
            if (empty($model->code) || static::withTrashed()->where('code', $model->code)->exists()) {
                $model->code = static::generateUniqueCode();
            }

            if (Auth::check() && blank($model->added_by)) {
                $model->added_by = Auth::id();
            }
        });

        static::updating(function (MeetingType $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
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

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'meeting_type_id');
    }
}

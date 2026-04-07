<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Tag extends Model
{
    use SoftDeletes, Filterable, Sortable, LogsModelActivity;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'type',
        'is_active',
        'is_system',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'name',
        'slug',
        'type',
        'created_at',
    ];

    protected $searchable = [
        'name',
        'slug',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'slug' => 'string',
            'color' => 'string',
            'type' => 'string',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            $tag->added_by = Auth::id();
        });

        static::updating(function (Tag $tag) {
            $tag->updated_by = Auth::id();
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_tags')
            ->withTimestamps();
    }

    public function taskTags()
    {
        return $this->hasMany(TaskTag::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Attachment extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'link_id',
        'link_type',
        'category',
        'file_name',
        'file_path',
        'file_type',
        'original_name',
        'file_size',
        'disk',
        'visibility',
        'is_primary',
        'is_active',
        'added_by'
    ];

    protected function casts(): array
    {
        return [
            'link_id' => 'integer',
            'link_type' => 'string',
            'category' => 'string',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'added_by' => 'integer',
        ];
    }

    public static function booted()
    {
        static::creating(function ($model) {
            $model->added_by = Auth::id() ?? null;
        });
    }

    public function link()
    {
        return $this->morphTo(__FUNCTION__, 'link_type', 'link_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    protected function getActivityLogName(): string
    {
        $module = $this->resolveActivityModuleName();
        $category = $this->resolveActivityCategoryName();

        return $category !== null
            ? "{$module}_{$category}"
            : "{$module}_attachments";
    }

    protected function resolveActivityModuleName(): string
    {
        return match ($this->link_type) {
            ProjectNote::class => 'project',
            default => Str::snake(class_basename($this->link_type ?: static::class)),
        };
    }

    protected function resolveActivityCategoryName(): ?string
    {
        return match ($this->category) {
            'project_note' => 'note_attachments',
            null, '' => null,
            default => Str::snake($this->category),
        };
    }
}

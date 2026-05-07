<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attachment extends Model
{
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

    public function getUrlAttribute()
    {
        if (! $this->file_path) {
            return null;
        }

        $disk = $this->disk ?? config('filesystems.default');

        if ($disk === 's3' && $this->visibility === 'private') {
            return Storage::disk($disk)->temporaryUrl($this->file_path, now()->addMinutes(15));
        }

        return Storage::disk($disk)->url($this->file_path);
    }

    protected function getActivityLogName(): string
    {
        $milestone = $this->resolveActivityMilestoneName();
        $category = $this->resolveActivityCategoryName();

        return $category !== null
            ? "{$milestone}_{$category}"
            : "{$milestone}_attachments";
    }

    protected function resolveActivityMilestoneName(): string
    {
        return match ($this->link_type) {
            ProjectNote::class => 'project',
            TaskNote::class => 'task',
            default => Str::snake(class_basename($this->link_type ?: static::class)),
        };
    }

    protected function resolveActivityCategoryName(): ?string
    {
        return match ($this->category) {
            'project_note' => 'note_attachments',
            'task_note' => 'note_attachments',
            null, '' => null,
            default => Str::snake($this->category),
        };
    }
}

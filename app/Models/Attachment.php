<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
        'status',
        'added_by'
    ];

    protected function casts(): array
    {
        return [
            'link_id' => 'integer',
            'link_type' => 'string',
            'category' => 'string',
            'is_primary' => 'boolean',
            'status' => 'boolean',
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Attachment extends Model
{
    protected $fillable = [
        'link_id',
        'link_type',
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

    public static function booted()
    {
        static::creating(function ($shift) {
            $shift->added_by = Auth::id() ?? null;
        });
    }

    public function link()
    {
        return $this->morphTo(__FUNCTION__, 'link_type', 'link_id');
    }
}

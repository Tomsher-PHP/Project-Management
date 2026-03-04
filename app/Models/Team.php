<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Team extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        "name",
        "status",
        "added_by",
        "updated_by"
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public static function booted()
    {
        static::creating(function ($model) {
            $model->added_by = Auth::id() ?? null;
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id() ?? null;
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('team_role', 'joined_at')
            ->withTimestamps();
    }

    public function leader()
    {
        return $this->belongsToMany(User::class)
            ->wherePivot('team_role', 'owner')
            ->limit(1);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'link', 'link_type', 'link_id');
    }

    public function primaryAttachment()
    {
        return $this->morphOne(Attachment::class, 'link', 'link_type', 'link_id')
            ->where('is_primary', 1);
    }

    public function getTeamAvatarUrlAttribute()
    {
        if ($this->primaryAttachment) {
            return Storage::disk($this->primaryAttachment->disk)->url($this->primaryAttachment->file_path);
        }

        return asset('assets/images/avatar/team_avatar.jpg');
    }
}

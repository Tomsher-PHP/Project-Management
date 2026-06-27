<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Team extends Model
{
    use SoftDeletes, Filterable, Sortable;

    protected $sortable = [
        'name'
    ];

    protected $fillable = [
        "name",
        "status",
        "added_by",
        "updated_by"
    ];

    protected $searchable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
            return $this->primaryAttachment->url;
        }

        return asset('assets/images/avatar/team_avatar.jpg');
    }
}

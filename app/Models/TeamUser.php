<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TeamUser extends Model
{
    protected $fillable = [
        "team_id",
        "user_id",
        "team_role",
        "joined_at",
        "status",
        "added_by",
        "updated_by"
    ];

    protected function casts(): array
    {
        return [
            'team_id' => 'integer',
            'user_id' => 'integer',
            'team_role' => 'string',
            'joined_at' => 'datetime',
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
}

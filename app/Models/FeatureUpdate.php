<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUpdate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'instructions',
        'feature_type',
        'module_name',
        'icon',
        'cover_image',
        'screenshots',
        'video_url',
        'target_type',
        'target_roles',
        'target_user_ids',
        'priority',
        'is_mandatory',
        'status',
        'publish_at',
        'expires_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'screenshots' => 'array',
            'target_roles' => 'array',
            'target_user_ids' => 'array',
            'is_mandatory' => 'boolean',
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function userStatuses(): HasMany
    {
        return $this->hasMany(FeatureUpdateUserStatus::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

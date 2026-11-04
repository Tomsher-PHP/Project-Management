<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUpdateUserStatus extends Model
{
    protected $fillable = [
        'feature_update_id',
        'user_id',
        'viewed_at',
        'acknowledged_at',
        'skipped_at',
        'last_viewed_at',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'skipped_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    public function featureUpdate(): BelongsTo
    {
        return $this->belongsTo(FeatureUpdate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

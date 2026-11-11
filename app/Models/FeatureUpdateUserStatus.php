<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUpdateUserStatus extends Model
{

    protected $fillable = [
        'feature_update_id',
        'user_id',
        'acknowledged_at',
        'skipped_at',
        'viewed_at',
        'last_viewed_at',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'skipped_at' => 'datetime',
            'viewed_at' => 'datetime',
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

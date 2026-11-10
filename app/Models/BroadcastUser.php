<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BroadcastUser extends Model
{
    protected $fillable = [
        'broadcast_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'broadcast_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


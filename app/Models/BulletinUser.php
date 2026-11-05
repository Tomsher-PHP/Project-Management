<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BulletinUser extends Model
{
    protected $fillable = [
        'bulletin_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'bulletin_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(Bulletin::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

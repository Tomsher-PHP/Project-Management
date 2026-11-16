<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReportPreference extends Model
{
    protected $fillable = [
        'user_id',
        'report_key',
        'is_enabled',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_enabled' => 'boolean',
            'updated_by' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

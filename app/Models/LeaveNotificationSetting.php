<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveNotificationSetting extends Model
{
    protected $fillable = [
        'user_ids',
        'external_emails',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'user_ids' => 'array',
            'external_emails' => 'array',
            'is_enabled' => 'boolean',
        ];
    }
}

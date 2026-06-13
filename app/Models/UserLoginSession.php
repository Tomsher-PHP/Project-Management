<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLoginSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'login_at',
        'logout_at',
        'ip_address',
        'country',
        'city',
        'browser',
        'platform',
        'device',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'login_at' => 'datetime',
            'logout_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

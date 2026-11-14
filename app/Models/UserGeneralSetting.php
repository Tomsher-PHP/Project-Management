<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGeneralSetting extends Model
{
    protected $fillable = [
        'user_id',
        'kanban_view',
        'theme',
        'send_daily_work_report',
    ];

    protected $casts = [
        'send_daily_work_report' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

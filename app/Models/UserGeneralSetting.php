<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGeneralSetting extends Model
{
    protected $fillable = [
        'user_id',
        'kanban_view',
        'theme'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

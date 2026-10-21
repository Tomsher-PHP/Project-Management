<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectNotificationDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'conditions',
        'setting_value',
    ];

    protected $casts = [
        'conditions' => 'array',
        'setting_value' => 'array',
    ];
}

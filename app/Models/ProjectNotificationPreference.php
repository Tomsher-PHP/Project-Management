<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'notification_type',
        'is_enabled',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}

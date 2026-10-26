<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectServiceType extends Model
{
    protected $table = 'project_service_types';

    protected $fillable = [
        'project_id',
        'service_type_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class)->withTrashed();
    }
}

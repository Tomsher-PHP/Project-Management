<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHostingStatusHistory extends Model
{
    protected $fillable = [
        'project_hosting_record_id',
        'from_status_id',
        'to_status_id',
        'changed_at',
        'changed_by',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function hostingRecord(): BelongsTo
    {
        return $this->belongsTo(ProjectHostingRecord::class, 'project_hosting_record_id');
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(HostingServiceStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(HostingServiceStatus::class, 'to_status_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

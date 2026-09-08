<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingStatus extends Model
{
    use SoftDeletes, Filterable, Sortable, LogsModelActivity;

    // Status codes
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_RESCHEDULED = 'rescheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Status types
    public const TYPE_OPEN = 'open';
    public const TYPE_IN_PROGRESS = 'in_progress';
    public const TYPE_COMPLETED = 'completed';
    public const TYPE_CANCELLED = 'cancelled';

    protected $fillable = [
        'name',
        'code',
        'color',
        'type',
        'sort_order',
        'is_default',
        'is_completed',
        'is_system',
        'is_active',
    ];

    protected $sortable = [
        'name',
        'code',
        'type',
        'sort_order',
        'is_active',
    ];

    protected $searchable = [
        'name',
        'code',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'code' => 'string',
            'color' => 'string',
            'type' => 'string',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_completed' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

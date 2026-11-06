<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    use Filterable, Sortable;

    public const PRIORITY_URGENT = 1;
    public const PRIORITY_IMPORTANT = 2;
    public const PRIORITY_NORMAL = 3;

    public const STATUS_DRAFT = 1;
    public const STATUS_PUBLISHED = 2;
    public const STATUS_ARCHIVED = 3;

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_TEAMS = 'teams';
    public const AUDIENCE_USERS = 'users';

    public const ATTACHMENT_CATEGORY = 'broadcast_attachment';

    protected $fillable = [
        'title',
        'content',
        'broadcast_category_id',
        'priority',
        'audience_type',
        'target_team_ids',
        'status',
        'start_date',
        'end_date',
        'published_at',
        'added_by',
        'updated_by',
    ];

    protected $searchable = ['title'];

    protected $sortable = [
        'title',
        'broadcast_category_id',
        'priority',
        'audience_type',
        'start_date',
        'end_date',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'status' => 'integer',
            'target_team_ids' => 'array',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'published_at' => 'datetime',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BroadcastCategory::class, 'broadcast_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BroadcastUser::class);
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'broadcast_users')->withTimestamps();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'link', 'link_type', 'link_id')
            ->where('category', self::ATTACHMENT_CATEGORY);
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_URGENT => 'Urgent',
            self::PRIORITY_IMPORTANT => 'Important',
            self::PRIORITY_NORMAL => 'Normal',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_DRAFT => '#64748b',
            self::STATUS_PUBLISHED => '#22c55e',
            self::STATUS_ARCHIVED => '#f59e0b',
        ];
    }

    public static function audienceOptions(): array
    {
        return [
            self::AUDIENCE_ALL => 'All Users',
            self::AUDIENCE_TEAMS => 'Specific Teams',
            self::AUDIENCE_USERS => 'Specific Users',
        ];
    }
}


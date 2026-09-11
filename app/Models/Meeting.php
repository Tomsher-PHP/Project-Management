<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Meeting extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable, LogsModelActivity, HasFormOptions;

    public const MEETING_FILE_CATEGORY = "meeting";

    protected $fillable = [
        'project_id',
        'meeting_type_id',
        'meeting_location_id',
        'meeting_status_id',
        'organizer_id',
        'title',
        'description',
        'minutes',
        'start_at',
        'end_at',
        'url',
        'location_details',
        'reminder_sent_at',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'title',
        'start_at',
        'end_at',
        'created_at',
    ];

    protected $searchable = [
        'title',
        'description',
        'location_details',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'meeting_type_id' => 'integer',
            'meeting_location_id' => 'integer',
            'meeting_status_id' => 'integer',
            'organizer_id' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Meeting $model) {
            if (Auth::check() && blank($model->added_by)) {
                $model->added_by = Auth::id();
            }
        });

        static::updating(function (Meeting $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function scopeAccessibleBy($query, $user)
    {
        if (! $user) {
            return $query;
        }

        if ($user->is_super_admin || $user->can('meeting.view_all_meetings') || $user->can('meeting.view_all')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('meetings.organizer_id', $user->id)
                ->orWhere('meetings.added_by', $user->id)
                ->orWhereHas('participants', function ($pq) use ($user) {
                    $pq->where('user_id', $user->id);
                })
                ->orWhereHas('project', function ($pq) use ($user) {
                    $pq->accessibleBy($user);
                });
        });
    }

    /**
     * Check if meeting minutes can be added or edited.
     */
    public function canAddMinutes(): bool
    {
        if (! $this->start_at || ! $this->start_at->copy()->shiftTimezone(config('constants.timezone'))->isPast()) {
            return false;
        }

        $statusCode = strtolower($this->meetingStatus?->code ?? '');
        $statusName = strtolower($this->meetingStatus?->name ?? '');

        if (
            in_array($statusCode, [MeetingStatus::STATUS_RESCHEDULED, MeetingStatus::STATUS_CANCELLED], true) ||
            in_array($statusName, ['rescheduled', 'cancelled'], true)
        ) {
            return false;
        }

        return true;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

    public function meetingType(): BelongsTo
    {
        return $this->belongsTo(MeetingType::class, 'meeting_type_id')->withTrashed();
    }

    public function meetingLocation(): BelongsTo
    {
        return $this->belongsTo(MeetingLocation::class, 'meeting_location_id')->withTrashed();
    }

    public function meetingStatus(): BelongsTo
    {
        return $this->belongsTo(MeetingStatus::class, 'meeting_status_id')->withTrashed();
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class, 'meeting_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_participants', 'meeting_id', 'user_id')
            ->withPivot(['is_external', 'name', 'email', 'phone', 'send_email'])
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MeetingTag::class, 'meeting_meeting_tag', 'meeting_id', 'meeting_tag_id')
            ->withTimestamps();
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Attachment::class, 'link');
    }
}

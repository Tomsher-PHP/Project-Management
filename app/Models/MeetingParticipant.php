<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class MeetingParticipant extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable, LogsModelActivity;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'is_external',
        'name',
        'email',
        'phone',
        'send_email',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'name',
        'email',
        'created_at',
    ];

    protected $searchable = [
        'name',
        'email',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'meeting_id' => 'integer',
            'user_id' => 'integer',
            'is_external' => 'boolean',
            'send_email' => 'boolean',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MeetingParticipant $model) {
            if (Auth::check() && blank($model->added_by)) {
                $model->added_by = Auth::id();
            }
        });

        static::updating(function (MeetingParticipant $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

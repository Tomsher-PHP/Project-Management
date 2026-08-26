<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyShiftHourNotification extends Model
{
    protected $fillable = [
        'user_id',
        'user_shift_assignment_id',
        'work_date',
        'notification_type',
        'required_seconds',
        'worked_seconds',
        'short_seconds',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'user_shift_assignment_id' => 'integer',
            'work_date' => 'date',
            'required_seconds' => 'integer',
            'worked_seconds' => 'integer',
            'short_seconds' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userShiftAssignment()
    {
        return $this->belongsTo(UserShiftAssignment::class, 'user_shift_assignment_id');
    }

    public function shiftAssignment()
    {
        return $this->belongsTo(UserShiftAssignment::class, 'user_shift_assignment_id');
    }
}

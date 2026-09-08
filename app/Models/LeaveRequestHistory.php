<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequestHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'user_id',
        'action',
        'old_status',
        'new_status',
        'old_from_date',
        'old_to_date',
        'new_from_date',
        'new_to_date',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'old_from_date' => 'date',
        'old_to_date' => 'date',
        'new_from_date' => 'date',
        'new_to_date' => 'date',
        'metadata' => 'array',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(
            LeaveRequest::class
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

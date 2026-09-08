<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_file_upload_required',
        'is_paid',
        'status',
        'created_by',
        'updated_by',
        'color',
    ];

    protected $casts = [
        'is_file_upload_required' => 'boolean',
        'is_paid' => 'boolean',
        'status' => 'boolean',
    ];

    public function balances()
    {
        return $this->hasMany(UserLeaveBalance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function transactions()
    {
        return $this->hasMany(LeaveTransaction::class);
    }
}

<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'user_id',
        'department_id',
        'designation_id',
        'reporter_id',
        'manager_id',
        'employee_id',
        'gender',
        'phone',
        'whatsapp',
        'contact_person',
        'contact_person_number',
        'joining_date',
        'leaving_date',
        'dob',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'department_id' => 'integer',
            'designation_id' => 'integer',
            'reporter_id' => 'integer',
            'manager_id' => 'integer',
            'joining_date' => 'datetime',
            'leaving_date' => 'datetime',
            'dob' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id', 'id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    /*----------------Activity Log Customization----------------*/

    // Never show these fields in activity log details.
    protected array $activityLogExceptAttributes = [
        'user_id',
    ];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'user_id' => 'User',
            'department_id' => 'Department',
            'designation_id' => 'Designation',
            'reporter_id' => 'Reporter',
            'manager_id' => 'Manager',
            'employee_id' => 'Employee ID',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'department_id' => Department::find($value)?->name ?? $value,
            'designation_id' => Designation::find($value)?->name ?? $value,
            'reporter_id' => User::find($value)?->name ?? $value,
            'manager_id' => User::find($value)?->name ?? $value,
            default => $value,
        };
    }

    protected function getActivityParent(): array
    {
        return [
            'type' => User::class,
            'id' => $this->user_id,
        ];
    }
}

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

        'emergency_contacts',
        'native_address',
        'native_contacts',
        'marital_status',
        'children_count',
        'favorite_color',
        'shirt_sizes',
        'shoe_sizes',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'department_id' => 'integer',
            'designation_id' => 'integer',
            'reporter_id' => 'integer',
            'manager_id' => 'integer',
            'children_count' => 'integer',

            'joining_date' => 'date',
            'leaving_date' => 'date',
            'dob' => 'date',

            'emergency_contacts' => 'array',
            'native_contacts' => 'array',
            'shirt_sizes' => 'array',
            'shoe_sizes' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id')->withTrashed();
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id')->withTrashed();
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
            'emergency_contacts' => 'Emergency Contacts',
            'native_address' => 'Native Address',
            'native_contacts' => 'Native Contacts',
            'marital_status' => 'Marital Status',
            'children_count' => 'Children Count',
            'favorite_color' => 'Favorite Color',
            'shirt_sizes' => 'Shirt Sizes',
            'shoe_sizes' => 'Shoe Sizes',
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
            default => is_array($value) ? json_encode($value) : $value,
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

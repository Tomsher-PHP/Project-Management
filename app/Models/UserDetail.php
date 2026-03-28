<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
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
            'employee_id' => 'integer',
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
}

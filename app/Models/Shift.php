<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Shift extends Model
{
    protected $fillable = [
        'name',
        'time_from',
        'time_to',
        'break_duration',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'time_from' => 'datetime:H:i:s',
            'time_to' => 'datetime:H:i:s',
            'break_duration' => 'integer',
            'is_default' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function weekends()
    {
        return $this->hasMany(ShiftWeekend::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'shift_departments');
    }

    public function assignments()
    {
        return $this->hasMany(UserShiftAssignment::class);
    }

    public function getTimeFromFormattedAttribute()
    {
        return $this->time_from->format('h:i A');
    }

    public function getTimeToFormattedAttribute()
    {
        return $this->time_to->format('h:i A');
    }

    public function getBreakDurationFormattedAttribute()
    {
        $seconds = $this->break_duration;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d h : %02d m', $hours, $minutes);
    }

    public function setBreakDurationAttribute($value)
    {
        $this->attributes['break_duration'] = $value * 60;
    }
}

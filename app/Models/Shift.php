<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Shift extends Model
{
    use Filterable, Sortable;

    protected $fillable = [
        'name',
        'time_from',
        'time_to',
        'break_duration',
        'color_code',
        'is_default',
        'status',
    ];

    protected $sortable = [
        'name',
    ];

    protected $searchable = ['name'];

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

    public function getDurationAttribute()
    {
        $start = Carbon::parse($this->time_from);
        $end = Carbon::parse($this->time_to);

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        // Total shift seconds
        $totalSeconds = $start->diffInSeconds($end);

        // Subtract break seconds
        $workingSeconds = $totalSeconds - ($this->break_duration ?? 0);

        // Prevent negative values
        $workingSeconds = max(0, $workingSeconds);

        return CarbonInterval::seconds($workingSeconds)->cascade()->format('%h hr %i min');
    }
}

<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class UserShiftAssignment extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'user_id',
        'shift_id',
        'shift_name',
        'time_from',
        'time_to',
        'break_duration',
        'color_code',
        'date_from',
        'date_to',
        'reason',
    ];

    protected $searchable = ['shift_name', 'date_from', 'date_to'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'shift_id' => 'integer',
            'time_from' => 'datetime:H:i:s',
            'time_to' => 'datetime:H:i:s',
            'break_duration' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class)->withTrashed();
    }

    public function weekends()
    {
        return $this->hasMany(UserShiftWeekend::class);
    }

    public function getTimeFromFormattedAttribute()
    {
        return $this->time_from
            ? $this->time_from
                ->timezone(config('constants.timezone'))
                ->format(config('constants.time_format'))
            : null;
    }

    public function getTimeToFormattedAttribute()
    {
        return $this->time_to
            ? $this->time_to
                ->timezone(config('constants.timezone'))
                ->format(config('constants.time_format'))
            : null;
    }

    public function getBreakDurationFormattedAttribute()
    {
        $seconds = $this->break_duration;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
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

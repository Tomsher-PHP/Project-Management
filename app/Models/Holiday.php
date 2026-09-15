<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'from_date',
        'to_date',
        'description',
        'is_public',
        'applied_to',
        'is_active',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Users this holiday applies to.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'holiday_users'
        )->withTimestamps();
    }

    /**
     * Shifts this holiday applies to.
     */
    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(
            Shift::class,
            'holiday_shifts'
        )->withTimestamps();
    }

    public function appliesToUserOnDate(User $user, Carbon|string $date): bool
    {
        $date = $date instanceof Carbon
            ? $date->copy()->startOfDay()
            : Carbon::parse($date)->startOfDay();

        if (!$this->is_active) {
            return false;
        }

        if (
            $date->lt($this->from_date->copy()->startOfDay()) ||
            $date->gt($this->to_date->copy()->startOfDay())
        ) {
            return false;
        }

        // Holiday applies to everyone.
        if ($this->applied_to === 'all_users') {
            return true;
        }

        // Holiday applies to specific users.
        if ($this->applied_to === 'user') {
            return $this->users()
                ->where('users.id', $user->id)
                ->exists();
        }

        // Holiday applies to users assigned to specific shifts.
        if ($this->applied_to === 'shift') {
            $shiftIds = $this->shifts()
                ->pluck('shifts.id');

            if ($shiftIds->isEmpty()) {
                return false;
            }

            return UserShiftAssignment::query()
                ->where('user_id', $user->id)
                ->whereIn('shift_id', $shiftIds)
                ->whereDate('date_from', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('date_to')
                        ->orWhereDate('date_to', '>=', $date);
                })
                ->exists();
        }

        return false;
    }

    public static function forUserOnDate(
        User $user,
        Carbon|string $date
    ): ?self {
        $date = $date instanceof Carbon
            ? $date->copy()->startOfDay()
            : Carbon::parse($date)->startOfDay();

        $holidays = static::query()
            ->where('is_active', true)
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->with([
                'users:id',
                'shifts:id',
            ])
            ->orderBy('id')
            ->get();

        foreach ($holidays as $holiday) {
            if ($holiday->appliesToUserOnDate($user, $date)) {
                return $holiday;
            }
        }

        return null;
    }
}

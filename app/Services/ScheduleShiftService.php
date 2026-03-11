<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Models\UserShiftAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleShiftService
{

    public function schedule(array $data): void
    {
        $shift = Shift::with('weekends')->findOrFail($data['shift_id']);

        DB::transaction(function () use ($data, $shift) {

            foreach ($data['users'] as $userId) {

                $assignment = UserShiftAssignment::create([
                    'user_id' => $userId,

                    // snapshot fields
                    'shift_id' => $shift->id,
                    'shift_name' => $shift->name,
                    'time_from' => $shift->time_from,
                    'time_to' => $shift->time_to,
                    'break_duration' => $shift->break_duration,
                    'color_code' => $shift->color_code,

                    // schedule
                    'date_from' => $data['date_from'],
                    'date_to' => $data['date_to'],
                    'reason' => $data['reason'] ?? null,
                ]);

                $this->storeWeekends($assignment, $shift);
            }
        });
    }

    private function storeWeekends(UserShiftAssignment $assignment, $shift): void
    {
        if ($shift->weekends->isEmpty()) {
            return;
        }

        $rows = $shift->weekends->map(function ($weekend) {
            return [
                'weekday' => $weekend->weekday,
                'week_number' => $weekend->week_number,
            ];
        })->toArray();

        $assignment->weekends()->createMany($rows);
    }

    // Get start and end of the week
    public function getWeekRange(?string $week = null): array
    {
        $startOfWeek = Carbon::parse($week ?? now())->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SATURDAY);

        return [$startOfWeek, $endOfWeek];
    }

    // Generate all dates in the week
    public function getWeekDates(Carbon $startOfWeek): array
    {
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $startOfWeek->copy()->addDays($i);
        }
        return $dates;
    }

    // Fetch users and shifts
    public function getUsersAndShifts(): array
    {
        $users = User::where('user_type', '!=', 'super_admin')
            ->whereStatus(1)
            ->orderBy('name')
            ->get();

        $shifts = Shift::whereStatus(1)->orderBy('is_default', 'desc')->orderBy('name', 'asc')->get();

        return [$users, $shifts];
    }

    // Build calendar for a given week
    public function buildCalendar($users, $assignments, Carbon $startOfWeek, Carbon $endOfWeek): array
    {
        $calendar = [];

        foreach ($assignments as $assignment) {
            $start = Carbon::parse($assignment->date_from)->max($startOfWeek);
            $end = $assignment->date_to ? Carbon::parse($assignment->date_to)->min($endOfWeek) : $endOfWeek;

            $current = $start->copy();
            while ($current <= $end) {
                $calendar[$assignment->user_id][$current->toDateString()] = $assignment;
                $current->addDay();
            }
        }

        return $calendar;
    }

    // Get assignments for users in a given week
    public function getAssignments(Carbon $startOfWeek, Carbon $endOfWeek)
    {
        return UserShiftAssignment::where(function ($query) use ($startOfWeek, $endOfWeek) {
            $query->where('date_from', '<=', $endOfWeek)
                ->where(function ($q) use ($startOfWeek) {
                    $q->where('date_to', '>=', $startOfWeek)
                        ->orWhereNull('date_to');
                });
        })->get();
    }

    public function updateUserShift(int $userId, string $date, ?int $shiftId): void
    {
        $date = Carbon::parse($date);

        DB::transaction(function () use ($userId, $date, $shiftId) {

            $existing = UserShiftAssignment::where('user_id', $userId)
                ->whereDate('date_from', '<=', $date)
                ->whereDate('date_to', '>=', $date)
                ->first();

            if (!$existing) {
                $this->createSingleDayShift($userId, $date, $shiftId);
                return;
            }

            $oldFrom = Carbon::parse($existing->date_from);
            $oldTo = Carbon::parse($existing->date_to);

            $existingData = $existing->toArray();

            $existing->delete();

            // BEFORE RANGE
            if ($oldFrom->lt($date)) {
                $this->createRangeShift(
                    $userId,
                    $existingData,
                    $oldFrom,
                    $date->copy()->subDay()
                );
            }

            // NEW DAY SHIFT
            $this->createSingleDayShift($userId, $date, $shiftId);

            // AFTER RANGE
            if ($oldTo->gt($date)) {
                $this->createRangeShift(
                    $userId,
                    $existingData,
                    $date->copy()->addDay(),
                    $oldTo
                );
            }
        });
    }

    private function createSingleDayShift(int $userId, Carbon $date, ?int $shiftId): void
    {
        $shift = Shift::find($shiftId);

        $assignment = UserShiftAssignment::create([
            'user_id' => $userId,
            'shift_id' => $shift?->id,
            'shift_name' => $shift?->name ?? '',
            'time_from' => $shift?->time_from ?? '00:00:00',
            'time_to' => $shift?->time_to ?? '00:00:00',
            'break_duration' => $shift?->break_duration ?? 0,
            'color_code' => $shift?->color_code ?? '#6b7280',
            'date_from' => $date,
            'date_to' => $date,
        ]);

        $this->storeWeekends($assignment, $shift);
    }

    private function createRangeShift(int $userId, array $existingData, Carbon $from, Carbon $to): void
    {
        $shift = Shift::find($existingData['shift_id']);

        $assignment = UserShiftAssignment::create([
            'user_id' => $userId,
            'shift_id' => $existingData['shift_id'],
            'shift_name' => $existingData['shift_name'],
            'time_from' => $existingData['time_from'],
            'time_to' => $existingData['time_to'],
            'break_duration' => $existingData['break_duration'],
            'color_code' => $existingData['color_code'],
            'date_from' => $from,
            'date_to' => $to,
        ]);

        $this->storeWeekends($assignment, $shift);
    }
}

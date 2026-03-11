<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Models\UserShiftAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleShiftService
{

    // Create schedule
    public function schedule(array $data): void
    {
        DB::transaction(function () use ($data) {

            foreach ($data['users'] as $userId) {

                $this->applyShiftRange(
                    $userId,
                    Carbon::parse($data['date_from']),
                    Carbon::parse($data['date_to']),
                    $data['shift_id'],
                    $data['reason'] ?? null
                );
            }
        });
    }

    // Update schedule individual
    public function updateUserShift(int $userId, string $from, string $to, ?int $shiftId): void
    {
        $dateFrom = Carbon::parse($from);
        $dateTo = Carbon::parse($to);

        DB::transaction(function () use ($userId, $dateFrom, $dateTo, $shiftId) {

            $this->applyShiftRange($userId, $dateFrom, $dateTo, $shiftId);
        });
    }

    private function applyShiftRange(int $userId, Carbon $newFrom, Carbon $newTo, ?int $shiftId, ?string $reason = null): void
    {
        $shift = Shift::with('weekends')->find($shiftId);

        $existingAssignments = UserShiftAssignment::where('user_id', $userId)
            ->where(function ($q) use ($newFrom, $newTo) {
                $q->whereBetween('date_from', [$newFrom, $newTo])
                    ->orWhereBetween('date_to', [$newFrom, $newTo])
                    ->orWhere(function ($q2) use ($newFrom, $newTo) {
                        $q2->where('date_from', '<=', $newFrom)
                            ->where('date_to', '>=', $newTo);
                    });
            })
            ->get();

        foreach ($existingAssignments as $existing) {

            $oldFrom = Carbon::parse($existing->date_from);
            $oldTo = Carbon::parse($existing->date_to);

            $existingData = $existing->toArray();

            $existing->delete();

            // BEFORE
            if ($oldFrom->lt($newFrom)) {
                $this->createAssignment(
                    $userId,
                    $existingData,
                    $oldFrom,
                    $newFrom->copy()->subDay()
                );
            }

            // AFTER
            if ($oldTo->gt($newTo)) {
                $this->createAssignment(
                    $userId,
                    $existingData,
                    $newTo->copy()->addDay(),
                    $oldTo
                );
            }
        }

        $this->createAssignment(
            $userId,
            $shift,
            $newFrom,
            $newTo,
            $reason
        );
    }

    private function createAssignment(int $userId, $shiftData, Carbon $from, Carbon $to, ?string $reason = null): void
    {
        if ($from->gt($to)) {
            return;
        }

        if ($shiftData instanceof Shift) {

            $assignment = UserShiftAssignment::create([
                'user_id' => $userId,
                'shift_id' => $shiftData->id,
                'shift_name' => $shiftData->name,
                'time_from' => $shiftData->time_from,
                'time_to' => $shiftData->time_to,
                'break_duration' => $shiftData->break_duration,
                'color_code' => $shiftData->color_code,
                'date_from' => $from,
                'date_to' => $to,
                'reason' => $reason,
            ]);

            $this->storeWeekends($assignment, $shiftData);
        } else {

            $shift = Shift::find($shiftData['shift_id']);

            $assignment = UserShiftAssignment::create([
                'user_id' => $userId,
                'shift_id' => $shiftData['shift_id'],
                'shift_name' => $shiftData['shift_name'],
                'time_from' => $shiftData['time_from'],
                'time_to' => $shiftData['time_to'],
                'break_duration' => $shiftData['break_duration'],
                'color_code' => $shiftData['color_code'],
                'date_from' => $from,
                'date_to' => $to,
            ]);

            $this->storeWeekends($assignment, $shift);
        }
    }

    // Store week end data of corresponding assigned shift
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

    /** Get functions */


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
}

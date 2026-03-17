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
            $dateFrom = Carbon::parse($data['date_from']);
            $dateTo = $data['date_to'] ? Carbon::parse($data['date_to']) : null;

            foreach ($data['users'] as $userId) {

                $this->applyShiftRange(
                    $userId,
                    $dateFrom,
                    $dateTo,
                    $data['shift_id'],
                    $data['reason'] ?? null
                );
            }

            // AFTER ALL ASSIGNMENTS → SEND BULK NOTIFICATION
            $shift = Shift::find($data['shift_id']);

            app(NotificationService::class)->sendToMany(
                $data['users'],
                'Shift Assigned',
                "You have been assigned to shift '{$shift->name}' from {$dateFrom->format('Y-m-d')} to " . ($dateTo ? $dateTo->format('Y-m-d') : '--')
            );
        });
    }

    // Update schedule individual
    public function updateUserShift(int $userId, string $from, string $to, ?int $shiftId): void
    {
        $dateFrom = Carbon::parse($from);
        $dateTo = Carbon::parse($to);

        DB::transaction(function () use ($userId, $dateFrom, $dateTo, $shiftId) {

            $this->applyShiftRange($userId, $dateFrom, $dateTo, $shiftId);

            // Notify user
            $shift = Shift::find($shiftId);

            app(NotificationService::class)->send(
                $userId,
                'Shift Assigned',
                "You have been assigned to shift '{$shift->name}' from {$dateFrom->format('Y-m-d')} to " . ($dateTo ? $dateTo->format('Y-m-d') : '--')
            );
        });
    }

    private function applyShiftRange(int $userId, Carbon $newFrom, ?Carbon $newTo, ?int $shiftId, ?string $reason = null): void
    {
        $shift = Shift::with('weekends')->find($shiftId);

        $existingAssignments = UserShiftAssignment::where('user_id', $userId)
            ->where(function ($q) use ($newFrom, $newTo) {
                if ($newTo !== null) {
                    $q->where('date_from', '<=', $newTo);
                }

                $q->where(function ($q2) use ($newFrom) {
                    $q2->where('date_to', '>=', $newFrom)
                        ->orWhereNull('date_to');
                });
            })
            ->get();

        foreach ($existingAssignments as $existing) {

            $oldFrom = Carbon::parse($existing->date_from);
            $oldTo = $existing->date_to ? Carbon::parse($existing->date_to) : null;

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
            if ($newTo !== null && ($oldTo === null || $oldTo->gt($newTo))) {
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

    private function createAssignment(int $userId, $shiftData, Carbon $from, ?Carbon $to, ?string $reason = null): void
    {
        if ($to !== null && $from->gt($to)) {
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
        $users = User::where('is_super_admin', false)
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

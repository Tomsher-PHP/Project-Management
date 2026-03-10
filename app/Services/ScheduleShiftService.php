<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\UserShiftAssignment;
use Illuminate\Support\Facades\DB;

class ScheduleShiftService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

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
}

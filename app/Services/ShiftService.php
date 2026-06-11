<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftDepartment;
use App\Models\ShiftWeekend;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createShift(array $data): Shift
    {
        return DB::transaction(function () use ($data) {

            // Create shift
            $shift = Shift::create([
                'name' => $data['name'],
                'time_from' => Carbon::createFromFormat('g:i A', $data['start_time'])->format('H:i:s'),
                'time_to' => Carbon::createFromFormat('g:i A', $data['end_time'])->format('H:i:s'),
                'break_duration' => $data['break_duration'],
                'color_code' => $data['color_code'] ?? '#6b7280',
            ]);

            // Store weekends
            if (!empty($data['weekend_days']) && is_array($data['weekend_days'])) {
                $weekends = [];

                foreach ($data['weekend_days'] as $weekday => $weeks) {
                    foreach ($weeks as $weekNumber) {
                        $weekends[] = new ShiftWeekend([
                            'weekday' => $weekday,
                            'week_number' => $weekNumber,
                        ]);
                    }
                }

                $shift->weekends()->saveMany($weekends);
            }

            return $shift;
        });
    }

    public function updateShifts(Shift $shift, array $data): Shift
    {
        return DB::transaction(function () use ($shift, $data) {

            // Only Name and Color Code are editable during update
            $updateData = [
                'name' => $data['name'],
                'color_code' => $data['color_code'] ?? '#6b7280',
            ];

            $shift->update($updateData);

            return $shift;
        });
    }
}

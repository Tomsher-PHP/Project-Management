<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\ShiftWeekend;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shift = Shift::firstOrCreate(
            ['is_default' => true],
            [
                'name' => 'General Shift',
                'time_from' => '09:00:00',
                'time_to' => '18:00:00',
                'break_duration' => 60, // 1 hour
                'color_code' => '#6b7280',
                'status' => true,
            ]
        );

        // Weekend (Example: Sunday all weeks)
        $weekends = [];

        for ($week = 1; $week <= 5; $week++) {
            $weekends[] = [
                'shift_id' => $shift->id,
                'weekday' => 0, // Sunday
                'week_number' => $week,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ShiftWeekend::insert($weekends);
    }
}

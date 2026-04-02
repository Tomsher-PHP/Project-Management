<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\ShiftWeekend;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ShiftWeekend::truncate();
        Shift::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $shift = Shift::firstOrCreate(
            ['is_default' => true],
            [
                'name' => 'General Shift',
                'time_from' => '09:00:00',
                'time_to' => '18:00:00',
                'break_duration' => 60, // 1 hour
                'color_code' => '#f3f4f6',
                'is_active' => true,
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

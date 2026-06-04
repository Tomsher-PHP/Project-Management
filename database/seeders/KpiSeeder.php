<?php

namespace Database\Seeders;

use App\Models\Kpi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KpiSeeder extends Seeder
{
    public function run(): void
    {
        $kpis = [
            [
                'name' => 'Task Completion Rate',
                'description' => 'Measures percentage of tasks completed on or before deadline',
            ],
            [
                'name' => 'Attendance Score',
                'description' => 'Tracks punctuality and attendance consistency',
            ],
            [
                'name' => 'Productivity Index',
                'description' => 'Evaluates overall output efficiency',
            ],
            [
                'name' => 'Quality of Work',
                'description' => 'Measures accuracy and standard of delivered work',
            ],
            [
                'name' => 'Team Collaboration',
                'description' => 'Evaluates teamwork and communication effectiveness',
            ],
        ];

        foreach ($kpis as $index => $kpi) {
            Kpi::firstOrCreate(
                ['name' => $kpi['name']],
                [
                    'slug' => Str::slug($kpi['name']),
                    'description' => $kpi['description'],
                    'is_active' => 1,
                    'is_system' => 1,
                    'added_by' => null,
                    'updated_by' => null,
                ]
            );
        }
    }
}
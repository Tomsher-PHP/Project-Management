<?php

namespace Database\Seeders;

use App\Models\SprintGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SprintGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'Planning', 'color' => '#007bff'], // Blue
            ['name' => 'Development', 'color' => '#6610f2'], // Purple
            ['name' => 'Testing', 'color' => '#ffc107'], // Yellow/Orange
            ['name' => 'Deployment', 'color' => '#28a745'], // Green
        ];

        foreach ($groups as $index => $group) {
            SprintGroup::updateOrCreate(
                ['name' => $group['name']],
                [
                    'color' => $group['color'],
                    'sort_order' => $index + 1,
                    'is_system' => 1,
                    'is_active' => 1,
                ]
            );
        }
    }
}

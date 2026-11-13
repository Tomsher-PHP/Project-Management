<?php

namespace Database\Seeders;

use App\Models\ProjectStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProjectStatus::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $statuses = [
            ['name' => 'Planned', 'color' => '#6c757d', 'type' => 'open', 'is_completed' => 0],
            ['name' => 'Active', 'color' => '#007bff', 'type' => 'in_progress', 'is_completed' => 0],
            ['name' => 'On Hold', 'color' => '#ffc107', 'type' => 'open', 'is_completed' => 0],
            ['name' => 'Completed', 'color' => '#28a745', 'type' => 'completed', 'is_completed' => 1],
            ['name' => 'Cancelled', 'color' => '#dc3545', 'type' => 'archived', 'is_completed' => 0],
        ];

        foreach ($statuses as $key => $status) {
            ProjectStatus::create([
                'name' => $status['name'],
                'code' => ProjectStatus::generateUniqueCode(),
                'color' => $status['color'],
                'type' => $status['type'],
                'sort_order' => $key + 1,
                'is_default' => $key === 0,
                'is_completed' => $status['is_completed'],
                'is_system' => 1,
                'is_active' => 1,
            ]);
        }
    }
}

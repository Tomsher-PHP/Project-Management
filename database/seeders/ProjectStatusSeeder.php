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
            ['name' => 'Planned', 'code' => 'planned', 'color' => '#6c757d', 'type' => 'open', 'is_completed' => 0],
            ['name' => 'Active', 'code' => 'active', 'color' => '#007bff', 'type' => 'in_progress', 'is_completed' => 0],
            ['name' => 'On Hold', 'code' => 'on_hold', 'color' => '#ffc107', 'type' => 'open', 'is_completed' => 0],
            ['name' => 'Completed', 'code' => 'completed', 'color' => '#28a745', 'type' => 'closed', 'is_completed' => 1],
            ['name' => 'Cancelled', 'code' => 'cancelled', 'color' => '#dc3545', 'type' => 'closed', 'is_completed' => 1],
        ];

        foreach ($statuses as $key => $status) {
            ProjectStatus::create([
                'name' => $status['name'],
                'code' => $status['code'],
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

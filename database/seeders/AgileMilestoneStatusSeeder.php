<?php

namespace Database\Seeders;

use App\Models\AgileMilestoneStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgileMilestoneStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        AgileMilestoneStatus::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $statuses = [
            ['name' => 'Planned', 'code' => 'planned', 'color' => '#6c757d', 'type' => 'open', 'sort_order' => 1, 'is_default' => 1, 'is_completed' => 0, 'is_system' => 1],
            ['name' => 'In Progress', 'code' => 'in_progress', 'color' => '#007bff', 'type' => 'in_progress', 'sort_order' => 2, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            ['name' => 'On Hold', 'code' => 'on_hold', 'color' => '#ffc107', 'type' => 'open', 'sort_order' => 3, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            ['name' => 'Completed', 'code' => 'completed', 'color' => '#28a745', 'type' => 'closed', 'sort_order' => 4, 'is_default' => 0, 'is_completed' => 1, 'is_system' => 1],
        ];

        foreach ($statuses as $status) {
            AgileMilestoneStatus::create($status + ['is_active' => 1]);
        }
    }
}

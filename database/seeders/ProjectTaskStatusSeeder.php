<?php

namespace Database\Seeders;

use App\Models\ProjectTaskStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectTaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProjectTaskStatus::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $statuses = [
            ['flow_type' => 'linear', 'name' => 'To Do', 'code' => 'to_do', 'color' => '#6c757d', 'type' => 'open', 'sort_order' => 1, 'is_default' => 1, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'linear', 'name' => 'In Progress', 'code' => 'in_progress', 'color' => '#007bff', 'type' => 'in_progress', 'sort_order' => 2, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'linear', 'name' => 'In Review', 'code' => 'in_review', 'color' => '#17a2b8', 'type' => 'review', 'sort_order' => 3, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'linear', 'name' => 'Completed', 'code' => 'completed', 'color' => '#28a745', 'type' => 'closed', 'sort_order' => 4, 'is_default' => 0, 'is_completed' => 1, 'is_system' => 1],
            ['flow_type' => 'linear', 'name' => 'On Hold', 'code' => 'on_hold', 'color' => '#ffc107', 'type' => 'open', 'sort_order' => 5, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'agile', 'name' => 'To Do', 'code' => 'to_do', 'color' => '#6c757d', 'type' => 'open', 'sort_order' => 1, 'is_default' => 1, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'agile', 'name' => 'In Progress', 'code' => 'in_progress', 'color' => '#007bff', 'type' => 'in_progress', 'sort_order' => 2, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'agile', 'name' => 'In Review', 'code' => 'in_review', 'color' => '#17a2b8', 'type' => 'review', 'sort_order' => 3, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'agile', 'name' => 'Completed', 'code' => 'completed', 'color' => '#28a745', 'type' => 'closed', 'sort_order' => 4, 'is_default' => 0, 'is_completed' => 1, 'is_system' => 1],
            ['flow_type' => 'agile', 'name' => 'On Hold', 'code' => 'on_hold', 'color' => '#ffc107', 'type' => 'open', 'sort_order' => 5, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
        ];

        foreach ($statuses as $status) {
            DB::table('project_task_statuses')->insert($status + [
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TaskStatus::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $statuses = [
            // linear flow statuses
            ['flow_type' => 'linear', 'name' => 'Open', 'code' => 'open', 'color' => '#6c757d', 'type' => 'pending', 'sort_order' => 1, 'is_default' => 1, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'linear', 'name' => 'Processing', 'code' => 'processing', 'color' => '#007bff', 'type' => 'active', 'sort_order' => 2, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'linear', 'name' => 'Done', 'code' => 'done', 'color' => '#28a745', 'type' => 'completed', 'sort_order' => 3, 'is_default' => 0, 'is_completed' => 1, 'is_system' => 1],
            ['flow_type' => 'linear', 'name' => 'Freeze', 'code' => 'freeze', 'color' => '#ffc107', 'type' => 'pending', 'sort_order' => 4, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            
            // agile flow statuses
            ['flow_type' => 'agile', 'name' => 'To Do', 'code' => 'to_do', 'color' => '#6c757d', 'type' => 'pending', 'sort_order' => 1, 'is_default' => 1, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'agile', 'name' => 'In Progress', 'code' => 'in_progress', 'color' => '#007bff', 'type' => 'active', 'sort_order' => 2, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
            ['flow_type' => 'agile', 'name' => 'Completed', 'code' => 'completed', 'color' => '#28a745', 'type' => 'completed', 'sort_order' => 3, 'is_default' => 0, 'is_completed' => 1, 'is_system' => 1],
            ['flow_type' => 'agile', 'name' => 'On Hold', 'code' => 'on_hold', 'color' => '#ffc107', 'type' => 'pending', 'sort_order' => 4, 'is_default' => 0, 'is_completed' => 0, 'is_system' => 1],
        ];

        foreach ($statuses as $status) {
            TaskStatus::create($status + [
                'is_active' => 1,
            ]);
        }
    }
}

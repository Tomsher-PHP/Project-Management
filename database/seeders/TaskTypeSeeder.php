<?php

namespace Database\Seeders;

use App\Models\TaskType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TaskType::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $taskTypes = [
            ['name' => 'Normal', 'code' => 'normal', 'color' => '#a9aaa9', 'sort_order' => 1, 'is_default' => 1, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Feature', 'code' => 'feature', 'color' => '#22C55E', 'sort_order' => 2, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Bug', 'code' => 'bug', 'color' => '#EF4444', 'sort_order' => 3, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Test', 'code' => 'test', 'color' => '#3B82F6', 'sort_order' => 4, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Research', 'code' => 'research', 'color' => '#8B5CF6', 'sort_order' => 5, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
        ];

        foreach ($taskTypes as $taskType) {
            TaskType::query()->create($taskType);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\TaskMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskModeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('task_modes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $taskModes = [
            [
                'name' => 'New',
                'code' => 'new',
                'description' => 'New planned work.',
                'color' => '#3B82F6',
                'is_rework' => 0,
                'is_productive' => 1,
                'track_performance' => 0,
                'sort_order' => 1,
                'is_active' => 1,
                'is_system' => 1,
                'is_default' => 1,
            ],
            [
                'name' => 'Rework',
                'code' => 'rework',
                'description' => 'Work caused by revisions or repeated effort.',
                'color' => '#F59E0B',
                'is_rework' => 1,
                'is_productive' => 0,
                'track_performance' => 1,
                'sort_order' => 2,
                'is_active' => 1,
                'is_system' => 1,
                'is_default' => 0,
            ],
            [
                'name' => 'Change Request',
                'code' => 'change_request',
                'description' => 'Work introduced through a client or internal change request.',
                'color' => '#06B6D4',
                'is_rework' => 0,
                'is_productive' => 1,
                'track_performance' => 1,
                'sort_order' => 3,
                'is_active' => 1,
                'is_system' => 1,
                'is_default' => 0,
            ],
        ];

        foreach ($taskModes as $taskMode) {
            TaskMode::create($taskMode);
        }
    }
}

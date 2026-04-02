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
            'Planned',
            'Active',
            'On Hold',
            'Completed',
            'Cancelled',
            'Re Work'
        ];

        foreach ($statuses as $key => $status) {
            ProjectStatus::create([
                'name' => $status,
                'sort_order' => $key + 1,
                'default' => 1,
                'status' => 1,
            ]);
        }
    }
}

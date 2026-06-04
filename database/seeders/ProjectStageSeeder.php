<?php

namespace Database\Seeders;

use App\Models\ProjectStage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProjectStage::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $stages = [
            ['name' => 'Planning', 'code' => 'planning', 'color' => '#000000'],
            ['name' => 'Design', 'code' => 'design', 'color' => '#9B59B6'],
            ['name' => 'Development', 'code' => 'development', 'color' => '#3498DB'],
            ['name' => 'Testing', 'code' => 'testing', 'color' => '#F1C40F'],
            ['name' => 'Deployment', 'code' => 'deployment', 'color' => '#E67E22'],
            ['name' => 'Maintenance', 'code' => 'maintenance', 'color' => '#2ECC71'],
        ];

        foreach ($stages as $key => $stage) {
            ProjectStage::create([
                'name' => $stage['name'],
                'code' => $stage['code'],
                'color' => $stage['color'],
                'sort_order' => $key + 1,
                'is_default' => $key === 0,
                'is_system' => 1,
                'is_active' => 1,
            ]);
        }
    }
}

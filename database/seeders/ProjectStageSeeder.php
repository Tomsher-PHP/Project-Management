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
            'Planning',
            'Design',
            'Development',
            'Testing',
            'Deployment',
            'Maintenance'
        ];

        foreach ($stages as $key => $stage) {
            ProjectStage::create([
                'name' => $stage,
                'order' => $key + 1,
                'default' => 1,
                'status' => 1,
            ]);
        }
    }
}

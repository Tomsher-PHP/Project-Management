<?php

namespace Database\Seeders;

use App\Models\AgileSprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgileSprintSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        AgileSprint::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $sprints = [
            [
                'name' => 'Sprint 1',
                'color' => '#2563EB',
                'description' => 'Default sprint slot for the first agile delivery cycle.',
            ],
            [
                'name' => 'Sprint 2',
                'color' => '#0EA5E9',
                'description' => 'Default sprint slot for the second agile delivery cycle.',
            ],
            [
                'name' => 'Sprint 3',
                'color' => '#14B8A6',
                'description' => 'Default sprint slot for the third agile delivery cycle.',
            ],
        ];

        foreach ($sprints as $key => $sprint) {
            AgileSprint::create([
                'name' => $sprint['name'],
                'color' => $sprint['color'],
                'description' => $sprint['description'],
                'sort_order' => $key + 1,
                'default' => 1,
                'status' => 1,
            ]);
        }
    }
}

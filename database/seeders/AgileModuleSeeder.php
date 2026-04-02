<?php

namespace Database\Seeders;

use App\Models\AgileModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgileModuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        AgileModule::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $modules = [
            ['Design', '#9B59B6', 'UX/UI design (Figma, Photoshop)'],
            ['Frontend', '#3498DB', 'HTML, CSS, UI development'],
            ['Backend', '#E74C3C', 'Server-side development'],
            ['QA Testing', '#F1C40F', 'Testing and quality assurance'],
            ['Bug Fixing', '#E67E22', 'Fixing bugs and issues'],
            ['DevOps', '#2C3E50', 'Deployment and infrastructure'],
            ['SEO', '#27AE60', 'Search engine optimization'],
        ];

        $sortOrder = 1;
        foreach ($modules as  [$name, $color, $description]) {
            AgileModule::create([
                'name' => $name,
                'color' => $color,
                'description' => $description,
                'is_default' => true,
                'sort_order' => $sortOrder,
            ]);
            $sortOrder++;
        }
    }
}

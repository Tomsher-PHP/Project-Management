<?php

namespace Database\Seeders;

use App\Models\AgileMilestone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgileMilestoneSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        AgileMilestone::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $milestones = [
            ['Design', '#9B59B6', 'UX/UI design (Figma, Photoshop)'],
            ['Frontend', '#3498DB', 'HTML, CSS, UI development'],
            ['Backend', '#E74C3C', 'Server-side development'],
            ['QA Testing', '#F1C40F', 'Testing and quality assurance'],
            ['Bug Fixing', '#E67E22', 'Fixing bugs and issues'],
            ['DevOps', '#2C3E50', 'Deployment and infrastructure'],
            ['SEO', '#27AE60', 'Search engine optimization'],
        ];

        $sortOrder = 1;
        foreach ($milestones as  [$name, $color, $description]) {
            AgileMilestone::create([
                'name' => $name,
                'color' => $color,
                'description' => $description,
                'is_system' => true,
                'sort_order' => $sortOrder,
            ]);
            $sortOrder++;
        }
    }
}

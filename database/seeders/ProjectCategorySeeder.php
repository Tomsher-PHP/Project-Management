<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Project Categories
        $array = [
            'Web Development',
            'Mobile Application',
            'API Development',
            'UI/UX Design',
            'Maintenance & Support',
            'Research & Development',
        ];

        foreach ($array as $key => $name) {
            ProjectCategory::firstOrCreate(
                ['name' => $name],
                [
                    'sort_order' => $key + 1,
                    'is_active' => 1,
                ]
            );
        }
    }
}

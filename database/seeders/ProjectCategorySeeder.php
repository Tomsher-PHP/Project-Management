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
                    'order' => $key + 1,
                    'default' => 1,
                    'status' => 1,
                ]
            );
        }
    }
}

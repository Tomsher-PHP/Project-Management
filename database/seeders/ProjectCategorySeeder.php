<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
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
            $category = ProjectCategory::withTrashed()->where('name', $name)->first();

            if ($category) {
                if (empty($category->code)) {
                    $category->code = ProjectCategory::generateUniqueCode();
                    $category->save();
                }

                continue;
            }

            ProjectCategory::create([
                'name' => $name,
                'code' => ProjectCategory::generateUniqueCode(),
                'sort_order' => $key + 1,
                'is_system' => 1,
                'is_active' => 1,
            ]);
        }
    }
}

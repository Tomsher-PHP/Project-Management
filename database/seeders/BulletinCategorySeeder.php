<?php

namespace Database\Seeders;

use App\Models\BulletinCategory;
use Illuminate\Database\Seeder;

class BulletinCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'General',
                'color' => '#64748B',
                'sort_order' => 1,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Important Notice',
                'color' => '#EF4444',
                'sort_order' => 2,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'HR & Policies',
                'color' => '#3B82F6',
                'sort_order' => 3,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Events',
                'color' => '#8B5CF6',
                'sort_order' => 4,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance',
                'color' => '#F59E0B',
                'sort_order' => 5,
                'is_system' => true,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            BulletinCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}

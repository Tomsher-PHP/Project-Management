<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;

class ProjectCategoryCodeSeeder extends Seeder
{
    /**
     * Backfill or normalize unique 6-digit numeric codes for existing project categories.
     */
    public function run(): void
    {
        ProjectCategory::withTrashed()
            ->orderBy('id')
            ->each(function (ProjectCategory $category) {
                if (preg_match('/^\d{6}$/', (string) $category->code)) {
                    return;
                }

                $category->code = ProjectCategory::generateUniqueCode();
                $category->save();
            });
    }
}


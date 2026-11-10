<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;

class ProjectCategoryCodeSeeder extends Seeder
{
    /**
     * Backfill unique codes for existing project categories that do not have one.
     */
    public function run(): void
    {
        ProjectCategory::withTrashed()
            ->where(function ($query) {
                $query->whereNull('code')
                    ->orWhere('code', '');
            })
            ->orderBy('id')
            ->each(function (ProjectCategory $category) {
                $category->code = ProjectCategory::generateUniqueCode();
                $category->save();
            });
    }
}


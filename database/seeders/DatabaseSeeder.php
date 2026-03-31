<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ConfigurationSeeder::class,
            RolePermissionSeeder::class,
            SuperAdminUserSeeder::class,
            DefaultShiftSeeder::class,
            ProjectStatusSeeder::class,
            ProjectStageSeeder::class,
            AgileModuleSeeder::class,
            AgileSprintSeeder::class,
            // DepartmentSeeder::class,
            // DesignationSeeder::class,
            // TechnologySeeder::class,
            // ProjectCategorySeeder::class,
            // IndustrySeeder::class,
        ]);
    }
}

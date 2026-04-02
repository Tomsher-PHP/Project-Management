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
            // Configuration related seeders
            ConfigurationSeeder::class,

            // User and role related seeders
            RolePermissionSeeder::class,
            SuperAdminUserSeeder::class,

            // Team related seeders
            DefaultShiftSeeder::class,

            // Organization related seeders
            DepartmentSeeder::class,
            DesignationSeeder::class,
            TechnologySeeder::class,
            ProjectCategorySeeder::class,
            IndustrySeeder::class,

            // Project related seeders            
            ProjectStatusSeeder::class,
            ProjectStageSeeder::class,
            AgileModuleSeeder::class,
            AgileSprintSeeder::class,
            AgileModuleStatusSeeder::class,
            AgileSprintStatusSeeder::class,

            // Country related seeders
            CountrySeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's all system resources.
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
            CustomerProfileGradeSeeder::class,

            // Project related seeders
            ProjectStatusSeeder::class,
            TaskStatusSeeder::class,
            TaskTypeSeeder::class,
            TaskModeSeeder::class,
            ProjectStageSeeder::class,
            TagsSeeder::class,
            AgileMilestoneSeeder::class,
            AgileSprintSeeder::class,
            AgileMilestoneStatusSeeder::class,
            AgileSprintStatusSeeder::class,
            KpiSeeder::class,
            HandoffPurposesSeeder::class,

            // Country related seeders
            CountrySeeder::class,
        ]);
    }
}

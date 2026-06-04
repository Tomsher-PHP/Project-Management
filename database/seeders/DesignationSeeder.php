<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $designations = [
            'Project Manager',
            'Product Manager',
            'Business Analyst',
            'Frontend Developer',
            'Backend Developer',
            'Full Stack Developer',
            'UI/UX Designer',
            'QA Engineer',
            'DevOps Engineer',
            'Sales Executive',
            'HR Executive',
            'Accountant',
            'IT Support',
        ];

        foreach ($designations as $key => $name) {
            Designation::updateOrCreate(
                ['name' => $name],
                [
                    'sort_order' => $key + 1,
                    'is_system' => 1,
                    'is_active' => 1,
                ]
            );
        }
    }
}

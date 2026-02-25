<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $array = [
            'Human Resources' => 'Handles recruitment, employee relations, and organizational development.',
            'Finance' => 'Manages financial planning, budgeting, and accounting.',
            'Information Technology' => 'Oversees technology infrastructure, software development, and IT support.',
            'Marketing' => 'Responsible for market research, advertising, and promotional activities.',
            'Sales' => 'Focuses on selling products or services and managing customer relationships.',
            'Customer Service' => 'Provides support and assistance to customers.',
            'Operations' => 'Ensures efficient business processes and supply chain management.',
            'Research and Development' => 'Conducts research and develops new products or services.',
        ];

        foreach ($array as $name => $description) {
            Department::create([
                'name' => $name,
                'description' => $description,
            ]);
        }
    }
}

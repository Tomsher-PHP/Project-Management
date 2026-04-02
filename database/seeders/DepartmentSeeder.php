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
            'Human Resources',
            'Finance',
            'Information Technology',
            'Marketing',
            'Sales',
            'Customer Service',
        ];

        foreach ($array as $key => $name) {
            Department::firstOrCreate(
                ['name' => $name],
                [
                    'sort_order' => $key + 1,
                    'status' => 1,
                ]
            );
        }
    }
}

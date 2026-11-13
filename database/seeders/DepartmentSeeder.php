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
            $department = Department::withTrashed()->where('name', $name)->first();

            if ($department) {
                if (empty($department->code)) {
                    $department->code = Department::generateUniqueCode();
                    $department->save();
                }

                continue;
            }

            Department::create([
                'name' => $name,
                'code' => Department::generateUniqueCode(),
                'sort_order' => $key + 1,
                'is_system' => 1,
                'is_active' => 1,
            ]);
        }
    }
}

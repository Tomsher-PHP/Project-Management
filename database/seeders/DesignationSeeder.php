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
        $array = [
            'Manager',
            'Project Head',
            'Business Development Manager',
            'Project Manager',
            'Product Manager',
            'Sales Manager',
            'Sr Developer',
            'Jr Developer',
            'Sr Designer',
            'Jr Designer',
            'Quality Analyst',
            'SEO Analyst',
            'Finance Specialist',
            'Sales Excecutive',
            'IT Support',
        ];
        
        foreach ($array as $key => $name) {
            Designation::create([
                'name' => $name,
                'order' => $key + 1,
                'default' => 1,
                'status' => 1,
            ]);
        }
    }
}

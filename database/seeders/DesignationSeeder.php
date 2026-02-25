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
            'Manager' => 'Oversees team operations and ensures project success.',
            'Developer' => 'Writes and maintains code for applications.',
            'Designer' => 'Creates visual concepts and designs for products.',
            'Analyst' => 'Analyzes data to provide insights and recommendations.',
            'Tester' => 'Tests software to identify bugs and ensure quality.',
            'HR Specialist' => 'Manages recruitment, employee relations, and organizational development.',
            'Finance Specialist' => 'Handles financial planning, budgeting, and accounting.',
            'IT Support' => 'Provides technical support and manages IT infrastructure.',
        ];
        
        foreach ($array as $name => $description) {
            Designation::create([
                'name' => $name,
                'description' => $description,
            ]);
        }
    }
}

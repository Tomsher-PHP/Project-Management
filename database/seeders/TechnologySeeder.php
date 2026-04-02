<?php

namespace Database\Seeders;

use App\Models\Technology;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Technologies
        $array = [
            // Core Languages
            'PHP',
            'JavaScript',
            'Python',
            'Java',
            'C#',

            // PHP
            'Laravel',

            // JavaScript
            'React',
            'Vue.js',
            'Angular',
            'Node.js',
            'Express.js',

            // Python
            'Django',
            'Flask',

            // Java
            'Spring Boot',

            // .NET
            '.NET',
            'ASP.NET Core',
        ];

        foreach ($array as $key => $name) {
            Technology::firstOrCreate(
                ['name' => $name],
                [
                    'sort_order' => $key + 1,
                    'status' => 1,
                ]
            );
        }
    }
}

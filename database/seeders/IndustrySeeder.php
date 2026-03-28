<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $array = [
            'Information Technology',
            'Software Development',
            'Web Development',
            'Digital Marketing',
            'E-Commerce',
            'Finance & Banking',
            'Healthcare',
            'Education',
            'Manufacturing',
            'Real Estate',
            'Consulting',
            'Telecommunications',
        ];

        foreach ($array as $key => $name) {
            Industry::firstOrCreate(
                ['name' => $name],
                [
                    'parent_id' => null,
                    'order' => $key + 1,
                    'status' => 1,
                ]
            );
        }
    }
}

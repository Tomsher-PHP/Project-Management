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
            $industry = Industry::withTrashed()->where('name', $name)->first();

            if ($industry) {
                if (empty($industry->code)) {
                    $industry->code = Industry::generateUniqueCode();
                    $industry->save();
                }

                continue;
            }

            Industry::create([
                'name' => $name,
                'code' => Industry::generateUniqueCode(),
                'parent_id' => null,
                'sort_order' => $key + 1,
                'is_system' => 1,
                'is_active' => 1,
            ]);
        }
    }
}

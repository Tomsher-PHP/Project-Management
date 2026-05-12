<?php

namespace Database\Seeders;

use App\Models\HandoffPurpose;
use Illuminate\Database\Seeder;

class HandoffPurposesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purposes = [
            'QA',
            'Review',
            'Deployment',
            'SEO',
            'Other',
        ];

        foreach ($purposes as $purpose) {
            HandoffPurpose::updateOrCreate(
                ['name' => $purpose],
                [
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}

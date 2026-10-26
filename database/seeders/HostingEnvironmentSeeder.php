<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HostingEnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        $environments = [
            [
                'name' => 'Production',
                'code' => 'production',
                'description' => 'Live environment serving end users.',
            ],
            [
                'name' => 'Staging',
                'code' => 'staging',
                'description' => 'Pre-production environment used for final verification.',
            ],
            [
                'name' => 'Development',
                'code' => 'development',
                'description' => 'Environment used for active development.',
            ],
            [
                'name' => 'Testing',
                'code' => 'testing',
                'description' => 'Environment used for testing and quality assurance.',
            ],
            [
                'name' => 'Disaster Recovery',
                'code' => 'disaster_recovery',
                'description' => 'Environment maintained for recovery during major incidents.',
            ],
        ];

        foreach ($environments as $index => $environment) {
            $exists = DB::table('hosting_environments')
                ->where('code', $environment['code'])
                ->exists();

            if (! $exists) {
                DB::table('hosting_environments')->insert([
                    'name' => $environment['name'],
                    'code' => $environment['code'],
                    'description' => $environment['description'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

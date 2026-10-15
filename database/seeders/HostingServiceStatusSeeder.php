<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HostingServiceStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Pending',
                'code' => 'pending',
                'description' => 'Service setup or activation is awaiting completion.',
            ],
            [
                'name' => 'Active',
                'code' => 'active',
                'description' => 'Service is currently active.',
            ],
            [
                'name' => 'Suspended',
                'code' => 'suspended',
                'description' => 'Service is temporarily suspended.',
            ],
            [
                'name' => 'Expired',
                'code' => 'expired',
                'description' => 'Service has reached its expiration date.',
            ],
            [
                'name' => 'Cancelled',
                'code' => 'cancelled',
                'description' => 'Service has been cancelled.',
            ],
            [
                'name' => 'Terminated',
                'code' => 'terminated',
                'description' => 'Service has been permanently terminated.',
            ],
        ];

        foreach ($statuses as $index => $status) {
            $exists = DB::table('hosting_service_statuses')
                ->where('code', $status['code'])
                ->exists();

            if (! $exists) {
                DB::table('hosting_service_statuses')->insert([
                    'name' => $status['name'],
                    'code' => $status['code'],
                    'description' => $status['description'],
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

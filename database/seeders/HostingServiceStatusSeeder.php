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
                'is_default' => true,
            ],
            [
                'name' => 'Active',
                'code' => 'active',
                'description' => 'Service is currently active.',
                'is_default' => false,
            ],
            [
                'name' => 'Suspended',
                'code' => 'suspended',
                'description' => 'Service is temporarily suspended.',
                'is_default' => false,
            ],
            [
                'name' => 'Expired',
                'code' => 'expired',
                'description' => 'Service has reached its expiration date.',
                'is_default' => false,
            ],
            [
                'name' => 'Cancelled',
                'code' => 'cancelled',
                'description' => 'Service has been cancelled.',
                'is_default' => false,
            ],
            [
                'name' => 'Terminated',
                'code' => 'terminated',
                'description' => 'Service has been permanently terminated.',
                'is_default' => false,
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
                    'is_default' => $status['is_default'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

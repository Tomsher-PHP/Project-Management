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
                'color' => '#F59E0B',
                'description' => 'Service setup or activation is awaiting completion.',
                'is_default' => false,
            ],
            [
                'name' => 'Active',
                'code' => 'active',
                'color' => '#22C55E',
                'description' => 'Service is currently active.',
                'is_default' => true,
            ],
            [
                'name' => 'Suspended',
                'code' => 'suspended',
                'color' => '#F97316',
                'description' => 'Service is temporarily suspended.',
                'is_default' => false,
            ],
            [
                'name' => 'Expired',
                'code' => 'expired',
                'color' => '#EF4444',
                'description' => 'Service has reached its expiration date.',
                'is_default' => false,
            ],
            [
                'name' => 'Cancelled',
                'code' => 'cancelled',
                'color' => '#64748B',
                'description' => 'Service has been cancelled.',
                'is_default' => false,
            ],
            [
                'name' => 'Terminated',
                'code' => 'terminated',
                'color' => '#475569',
                'description' => 'Service has been permanently terminated.',
                'is_default' => false,
            ],
        ];

        foreach ($statuses as $index => $status) {
            $existing = DB::table('hosting_service_statuses')
                ->where('code', $status['code'])
                ->first();

            if (! $existing) {
                DB::table('hosting_service_statuses')->insert([
                    'name' => $status['name'],
                    'code' => $status['code'],
                    'color' => $status['color'],
                    'description' => $status['description'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'is_system' => true,
                    'is_default' => $status['is_default'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('hosting_service_statuses')
                    ->where('id', $existing->id)
                    ->update([
                        'color' => $status['color'],
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}

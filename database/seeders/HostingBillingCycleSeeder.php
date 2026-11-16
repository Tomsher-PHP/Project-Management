<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HostingBillingCycleSeeder extends Seeder
{
    public function run(): void
    {
        $billingCycles = [
            [
                'name' => 'Monthly',
                'code' => 'monthly',
                'description' => 'Billed on a monthly basis.',
                'interval_months' => 1,
            ],
            [
                'name' => 'Quarterly',
                'code' => 'quarterly',
                'description' => 'Billed every three months.',
                'interval_months' => 3,
            ],
            [
                'name' => 'Semi-annually',
                'code' => 'semi_annually',
                'description' => 'Billed every six months.',
                'interval_months' => 6,
            ],
            [
                'name' => 'Annually',
                'code' => 'annually',
                'description' => 'Billed once every year.',
                'interval_months' => 12,
            ],
            [
                'name' => 'Every 2 Years',
                'code' => 'biennially',
                'description' => 'Billed once every two years.',
                'interval_months' => 24,
            ],
            [
                'name' => 'Every 3 Years',
                'code' => 'triennially',
                'description' => 'Billed once every three years.',
                'interval_months' => 36,
            ],
            [
                'name' => 'One-time',
                'code' => 'one_time',
                'description' => 'Single one-off payment with no recurring cycle.',
                'interval_months' => null,
            ],
        ];

        foreach ($billingCycles as $index => $cycle) {
            $exists = DB::table('hosting_billing_cycles')
                ->where('code', $cycle['code'])
                ->exists();

            if (! $exists) {
                DB::table('hosting_billing_cycles')->insert([
                    'name' => $cycle['name'],
                    'code' => $cycle['code'],
                    'description' => $cycle['description'],
                    'interval_months' => $cycle['interval_months'],
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

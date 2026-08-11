<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    private const CUSTOMER_COUNT = 15;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->cleanUpExistingCustomers();

            Customer::factory(self::CUSTOMER_COUNT)->create();
        });
    }

    private function cleanUpExistingCustomers(): void
    {
        CustomerContact::withTrashed()->forceDelete();
        Customer::withTrashed()->forceDelete();
    }
}

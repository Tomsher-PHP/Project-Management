<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            DummyUserSeeder::class,
        ]);

        DB::transaction(function () {
            Project::withTrashed()->forceDelete();
            Customer::withTrashed()->forceDelete();
        });

        Customer::factory(12)->create();
        Project::factory(12)->create();
    }
}

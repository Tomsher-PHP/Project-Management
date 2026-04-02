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
        //truncate tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('model_has_permissions')->truncate();
        // DB::table('model_has_roles')->truncate();
        // DB::table('role_has_permissions')->truncate();
        // DB::table('users')->truncate();
        // DB::table('user_details')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->call([
            AdminUserSeeder::class,
            DummyUserSeeder::class,
        ]);

        Customer::factory(12)->create();
        Project::factory(12)->create();
    }
}

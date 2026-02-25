<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $existingUser = User::where('email', 'superadmin@gmail.com')->first();

        if ($existingUser) {
            $existingUser->delete();
        }

        // Create new super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('12345678'),
            'user_type' => 'super_admin',
        ]);

        // Assign role
        $superAdmin->assignRole('Super Admin');

        // Create User Details
        $superAdmin->details()->create([
            'employee_id'     => 'EMP-001',
            'department_id'   => 1, // make sure department exists
            'designation_id'  => 1, // make sure designation exists
            'gender'          => 'male',
            'phone'    => '9999999999',
            'joining_date'    => now(),
        ]);

        ///// Create Admin User /////
        $existingAdmin = User::where('email', 'admin@gmail.com')->first();

        if ($existingAdmin) {
            $existingAdmin->delete();
        }

        // create admin user
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'user_type' => 'admin',
        ]);

        // Assign role to admin user
        $adminUser->assignRole('Admin');

        // Create Admin Details
        $adminUser->details()->create([
            'employee_id'     => 'EMP-002',
            'department_id'   => 1,
            'designation_id'  => 2,
            'gender'          => 'female',
            'phone'    => '8888888888',
            'joining_date'    => now(),
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create super admin user if not exists
        $existingUser = User::where('email', 'superadmin@gmail.com')->first();

        if ($existingUser) {
            $existingUser->delete();
        }

        // Create new super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('12345678'),
            'is_super_admin' => true,
        ]);

        // Assign role
        $superAdmin->assignRole('Owner');

        // Create User Details
        $superAdmin->details()->create([
            'employee_id'     => 'OWN-001',
            'gender'          => 'male',
            'joining_date'    => now(),
        ]);
    }
}

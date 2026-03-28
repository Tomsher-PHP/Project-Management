<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        $existingAdmin = User::where('email', 'admin@gmail.com')->first();

        if ($existingAdmin) {
            $existingAdmin->delete();
        }

        // create admin user
        $adminUser = User::create([
            'name' => 'Company Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
        ]);

        // Assign role to admin user
        $adminUser->assignRole('Admin');

        // Create Admin Details
        $adminUser->details()->create([
            'employee_id'     => 'EMP-002',
            'department_id'   => 1,
            'designation_id'  => 2,
            'gender'          => 'female',
            'joining_date'    => now(),
        ]);
    }
}

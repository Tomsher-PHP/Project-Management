<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DummyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['Team Member', 'Manager', 'Team Leader'];

        User::factory(30)->create()->each(function ($user) use ($roles) {

            // assign role
            $user->assignRole($roles[array_rand($roles)]);

            // create user details
            $user->details()->create([
                'employee_id' => 'EMP-' . rand(100, 999),
                'department_id' => null,
                'designation_id' => null,
                'gender' => fake()->randomElement(['male', 'female']),
                'joining_date' => fake()->dateTimeBetween('-2 years'),
            ]);
        });
    }
}

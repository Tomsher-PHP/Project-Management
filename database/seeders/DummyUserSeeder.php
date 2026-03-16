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
        User::factory(30)->create()->each(function ($user) {

            // assign role
            $user->assignRole('Team Member');

            // create user details
            $user->details()->create([
                'employee_id' => 'EMP-' . rand(100, 999),
                'department_id' => rand(1, 5),
                'designation_id' => rand(1, 5),
                'gender' => fake()->randomElement(['male', 'female']),
                'joining_date' => fake()->dateTimeBetween('-2 years'),
            ]);
        });
    }
}

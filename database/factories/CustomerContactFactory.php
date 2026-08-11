<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerContact>
 */
class CustomerContactFactory extends Factory
{
    protected $model = CustomerContact::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'landline' => fake()->optional(0.7)->phoneNumber(),
            'mobile' => fake()->phoneNumber(),
            'whatsapp' => fake()->optional(0.7)->phoneNumber(),
            'designation' => fake()->randomElement([
                'Procurement Manager',
                'Project Manager',
                'Operations Director',
                'General Manager',
                'Chief Executive Officer',
                'Technical Director',
                'Account Manager',
                'Purchasing Officer',
                'Managing Director',
            ]),
            'is_primary' => false,
            'is_active' => true,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}

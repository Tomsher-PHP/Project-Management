<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CustomerProfileGrade;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    protected static ?int $customerCodeCounter = null;

    public function configure(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            if ($customer->contacts()->count() === 0) {
                CustomerContact::factory()->primary()->create([
                    'customer_id' => $customer->id,
                ]);

                CustomerContact::factory()->count(fake()->numberBetween(1, 2))->create([
                    'customer_id' => $customer->id,
                    'is_primary' => false,
                ]);
            }
        });
    }

    public function definition(): array
    {
        $companyName = fake()->unique()->company();
        $countryId = Country::query()->inRandomOrder()->value('id');

        return [
            'customer_code' => $this->nextCustomerCode(),
            'name' => $companyName,
            'email' => fake()->unique()->companyEmail(),
            'industry_id' => Industry::query()->inRandomOrder()->value('id'),
            'customer_profile_grade_id' => CustomerProfileGrade::query()->inRandomOrder()->value('id'),
            'website' => fake()->optional()->url(),
            'registered_country_id' => $countryId,
            'emirate' => fake()->optional()->randomElement([
                'Abu Dhabi',
                'Dubai',
                'Sharjah',
                'Ajman',
                'Ras Al Khaimah',
                'Fujairah',
                'Umm Al Quwain',
            ]),
            'google_map_link' => fake()->optional()->url(),
            'company_address' => fake()->optional()->address(),
            'sales_person_id' => User::query()->inRandomOrder()->value('id'),
            'new_to_company' => fake()->boolean(35),
            'is_active' => true,
        ];
    }

    private function nextCustomerCode(): string
    {
        if (static::$customerCodeCounter === null) {
            $lastCustomerCode = Customer::withTrashed()->orderByDesc('id')->value('customer_code') ?? 'CUS00000';
            static::$customerCodeCounter = (int) substr($lastCustomerCode, 3);
        }

        static::$customerCodeCounter++;

        return 'CUS' . str_pad((string) static::$customerCodeCounter, 5, '0', STR_PAD_LEFT);
    }
}

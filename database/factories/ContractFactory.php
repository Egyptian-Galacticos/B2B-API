<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contractDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $estimatedDelivery = $this->faker->dateTimeBetween($contractDate, '+3 months');

        return [
            'contract_number' => 'CON-'.$this->faker->unique()->numerify('######'),
            'buyer_id' => \App\Models\User::factory(),
            'seller_id' => \App\Models\User::factory(),
            'status' => $this->faker->randomElement(['draft', 'pending', 'active', 'completed', 'cancelled']),
            'total_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'contract_date' => $contractDate,
            'estimated_delivery' => $estimatedDelivery,
            'shipping_address' => $this->faker->address(),
            'billing_address' => $this->faker->address(),
            'terms_and_conditions' => $this->faker->paragraphs(3, true),
            'metadata' => [
                'payment_terms' => $this->faker->randomElement(['Net 30', 'Net 60', 'COD', 'Prepaid']),
                'shipping_method' => $this->faker->randomElement(['Air', 'Sea', 'Land', 'Express']),
                'incoterms' => $this->faker->randomElement(['FOB', 'CIF', 'EXW', 'DDP']),
            ],
        ];
    }

    /**
     * Indicate that the contract is in draft status
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the contract is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rfq>
 */
class RfqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buyer_id'           => User::factory(),
            'seller_id'          => User::factory(),
            'initial_product_id' => Product::factory(),
            'initial_quantity'   => $this->faker->numberBetween(1, 100),
            'shipping_country'   => $this->faker->country(),
            'shipping_address'   => $this->faker->address(),
            'buyer_message'      => $this->faker->optional()->paragraph(),
            'status'             => $this->faker->randomElement(Rfq::VALID_STATUSES),
        ];
    }
}

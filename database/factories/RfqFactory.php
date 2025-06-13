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
        $buyers = User::role('buyer')->pluck('id');
        $sellers = User::role('seller')->pluck('id');
        $products = Product::pluck('id');

        return [
            'buyer_id' => $buyers->isNotEmpty()
                ? $buyers->random()
                : User::factory()->create()->assignRole('buyer')->id,
            'seller_id' => $sellers->isNotEmpty()
                ? $sellers->random()
                : User::factory()->create()->assignRole('seller')->id,
            'initial_product_id' => $products->isNotEmpty()
                ? $products->random()
                : Product::factory(),
            'initial_quantity' => $this->faker->numberBetween(1, 100),
            'shipping_country' => $this->faker->country(),
            'shipping_address' => $this->faker->address(),
            'buyer_message'    => $this->faker->optional()->paragraph(),
            'status'           => $this->faker->randomElement(Rfq::VALID_STATUSES),
        ];
    }
}

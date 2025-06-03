<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);
        $totalPrice = $quantity * $unitPrice;

        return [
            'quote_id' => \App\Models\Quote::factory(),
            'product_id' => \App\Models\Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'specifications' => $this->faker->sentence(),
        ];
    }
}

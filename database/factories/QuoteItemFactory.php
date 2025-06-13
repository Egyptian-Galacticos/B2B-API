<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 50);
        $unitPrice = $this->faker->randomFloat(2, 1, 500);

        $quotes = Quote::pluck('id');
        $products = Product::pluck('id');

        return [
            'quote_id' => $quotes->isNotEmpty()
                ? $quotes->random()
                : Quote::factory(),
            'product_id' => $products->isNotEmpty()
                ? $products->random()
                : Product::factory(),
            'quantity'   => $quantity,
            'unit_price' => $unitPrice,
            'notes'      => $this->faker->optional()->sentence(),
        ];
    }
}

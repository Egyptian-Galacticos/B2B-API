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

        return [
            'quote_id'   => Quote::factory(),
            'product_id' => Product::factory(),
            'quantity'   => $quantity,
            'unit_price' => $unitPrice,
            'notes'      => $this->faker->optional()->sentence(),
        ];
    }
}

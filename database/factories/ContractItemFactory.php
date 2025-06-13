<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ContractItem>
 */
class ContractItemFactory extends Factory
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

        $contracts = Contract::pluck('id');
        $products = Product::pluck('id');

        return [
            'contract_id' => $contracts->isNotEmpty()
                ? $contracts->random()
                : Contract::factory(),
            'product_id' => $products->isNotEmpty()
                ? $products->random()
                : Product::factory(),
            'quantity'       => $quantity,
            'unit_price'     => $unitPrice,
            'total_price'    => $totalPrice,
            'specifications' => $this->faker->sentence(),
        ];
    }
}

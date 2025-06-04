<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id'              => \App\Models\User::factory(),
            'sku'                    => strtoupper($this->faker->bothify('##??####')),
            'name'                   => $this->faker->words(3, true),
            'description'            => $this->faker->paragraph(),
            'hs_code'                => $this->faker->numerify('####.##.##'),
            'price'                  => $this->faker->randomFloat(2, 10, 10000),
            'currency'               => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'minimum_order_quantity' => $this->faker->numberBetween(1, 100),
            'lead_time_days'         => $this->faker->numberBetween(7, 90),
            'origin'                 => $this->faker->country(),
            'category_id'            => \App\Models\Category::factory(),
            'specifications'         => [
                'weight'   => $this->faker->randomFloat(2, 0.1, 100).' kg',
                'material' => $this->faker->word(),
                'color'    => $this->faker->colorName(),
            ],
            'certifications' => $this->faker->randomElements(['ISO 9001', 'CE', 'FDA', 'RoHS', 'UL'], $this->faker->numberBetween(0, 3)),
            'dimensions'     => [
                'length' => $this->faker->randomFloat(2, 1, 200),
                'width'  => $this->faker->randomFloat(2, 1, 200),
                'height' => $this->faker->randomFloat(2, 1, 200),
                'unit'   => 'cm',
            ],
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }
}

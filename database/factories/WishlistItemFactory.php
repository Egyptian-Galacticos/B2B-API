<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wishlist_id' => Wishlist::factory(),
            'product_id' => Product::factory(),
            'added_at' => $this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d H:i:s'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}

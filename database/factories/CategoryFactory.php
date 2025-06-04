<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'        => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'parent_id'   => null, // Will be set by specific states if needed
            'path'        => $this->faker->slug(),
            'level'       => 0,
            'is_active'   => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the category is a subcategory
     */
    public function subcategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => \App\Models\Category::factory(),
            'level'     => $this->faker->numberBetween(1, 3),
        ]);
    }
}

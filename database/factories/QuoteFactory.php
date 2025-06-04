<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote_number' => 'QUO-'.$this->faker->unique()->numerify('######'),
            'buyer_id'     => \App\Models\User::factory(),
            'seller_id'    => \App\Models\User::factory(),
            'status'       => $this->faker->randomElement(['draft', 'sent', 'accepted', 'declined', 'expired']),
            'total_amount' => $this->faker->randomFloat(2, 500, 50000),
            'currency'     => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'valid_until'  => $this->faker->dateTimeBetween('now', '+30 days'),
            'terms'        => $this->faker->paragraphs(2, true),
            'metadata'     => [
                'payment_terms' => $this->faker->randomElement(['Net 30', 'Net 60', 'COD', 'Prepaid']),
                'delivery_time' => $this->faker->numberBetween(7, 60).' days',
                'warranty'      => $this->faker->randomElement(['1 year', '2 years', '6 months', 'No warranty']),
            ],
        ];
    }

    /**
     * Indicate that the quote is in draft status
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the quote has been sent
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    /**
     * Indicate that the quote has been accepted
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }
}

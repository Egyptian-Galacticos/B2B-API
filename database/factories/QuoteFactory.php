<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\Rfq;
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
        $rfqIds = Rfq::pluck('id');

        return [
            'rfq_id' => $rfqIds->isNotEmpty()
                ? $rfqIds->random()
                : null,
            'total_price'    => $this->faker->randomFloat(2, 10, 10000),
            'seller_message' => $this->faker->optional()->paragraph(),
            'status'         => $this->faker->randomElement(Quote::VALID_STATUSES),
        ];
    }
}

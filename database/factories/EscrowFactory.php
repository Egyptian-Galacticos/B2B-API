<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Escrow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Escrow>
 */
class EscrowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Escrow::class;

    public function definition(): array
    {
        $contracts = Contract::pluck('id');

        return [
            'contract_id' => $contracts->isNotEmpty()
                ? $contracts->random()
                : Contract::factory(),
            'status'   => $this->faker->randomElement(['pending', 'released', 'refunded']),
            'amount'   => $this->faker->randomFloat(2, 100, 50000),
            'currency' => 'USD',
        ];
    }
}

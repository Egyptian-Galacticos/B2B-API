<?php

namespace Database\Factories;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $isCompleted = $this->faker->boolean(70);
        $paymentMethod = 'bank_transfer';

        return [
            // 'contract_id' => Contract::factory(),
            'type' => $this->faker->randomElement(['direct', 'escrow_release', 'refund']),
            'status' => $isCompleted ? 'completed' : $this->faker->randomElement(['pending', 'failed']),
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'currency' => 'USD',
            'payment_method' => $paymentMethod,
            'transaction_id' => $isCompleted
                ? 'txn_'.$this->faker->unique()->regexify('[A-Za-z0-9]{16}')
                : null,
            'metadata' => $isCompleted ? match ($paymentMethod) {
                'bank_transfer' => [
                    'bank_name' => $this->faker->randomElement(['CIB', 'HSBC', 'QNB']),
                    'account_number_last4' => $this->faker->numerify('####'),
                    'transaction_reference' => strtoupper($this->faker->bothify('BNK-##??##')),
                ],
            } : null,
            'processed_at' => $isCompleted
                ? $this->faker->dateTimeBetween('-30 days', 'now')
                : null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type'             => $this->faker->randomElement(['direct', 'contract']),
            'title'            => $this->faker->sentence(3),
            'seller_id'        => null,
            'buyer_id'         => null,
            'last_message_id'  => null,
            'last_activity_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'is_active'        => $this->faker->boolean(80),
        ];
    }

    /**
     * Create a conversation between a buyer and seller
     */
    public function betweenBuyerAndSeller(): static
    {
        return $this->state(function (array $attributes) {
            $buyers = User::role('buyer')->where('status', 'active')->pluck('id');
            $sellers = User::role('seller')->where('status', 'active')->pluck('id');

            if ($buyers->isEmpty() || $sellers->isEmpty()) {
                $allUsers = User::pluck('id');
                if ($allUsers->count() >= 2) {
                    $userIds = $allUsers->random(2);
                    $buyerId = $userIds[0];
                    $sellerId = $userIds[1];
                } else {
                    $buyerId = $allUsers->first();
                    $sellerId = $allUsers->first();
                }
            } else {
                $buyerId = $buyers->random();
                $sellerId = $sellers->random();
            }

            return [
                'buyer_id'  => $buyerId,
                'seller_id' => $sellerId,
                'type'      => 'direct',
                'title'     => 'Direct conversation between buyer and seller',
            ];
        });
    }

    /**
     * Create a conversation for contract discussions
     */
    public function forContract(): static
    {
        return $this->state(function (array $attributes) {
            $contracts = Contract::with(['buyer', 'seller'])->get();

            if ($contracts->isNotEmpty()) {
                $contract = $contracts->random();
                $buyerId = $contract->buyer_id;
                $sellerId = $contract->seller_id;
                $title = "Contract #{$contract->contract_number} Discussion";
            } else {
                $buyers = User::role('buyer')->where('status', 'active')->pluck('id');
                $sellers = User::role('seller')->where('status', 'active')->pluck('id');

                if ($buyers->isNotEmpty() && $sellers->isNotEmpty()) {
                    $buyerId = $buyers->random();
                    $sellerId = $sellers->random();
                } else {
                    $users = User::limit(2)->pluck('id');
                    $buyerId = $users->first();
                    $sellerId = $users->count() > 1 ? $users->last() : $users->first();
                }
                $title = 'Contract discussion';
            }

            return [
                'buyer_id'  => $buyerId,
                'seller_id' => $sellerId,
                'type'      => 'contract',
                'title'     => $title,
            ];
        });
    }

    public function configure()
    {
        return $this->afterCreating(function (Conversation $conversation) {
            if ($conversation->seller_id && $conversation->buyer_id) {
                $participantIds = [$conversation->seller_id, $conversation->buyer_id];
                $message = Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => fake()->randomElement($participantIds),
                ]);

                $conversation->update([
                    'last_message_id'  => $message->id,
                    'last_activity_at' => $message->sent_at,
                ]);
            }
        });
    }
}

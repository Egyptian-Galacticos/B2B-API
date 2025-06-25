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
            'participant_ids'  => [],
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
                $userIds = $allUsers->isNotEmpty()
                    ? $allUsers->random(min(2, $allUsers->count()))->toArray()
                    : [];
            } else {
                $userIds = [
                    $buyers->random(),
                    $sellers->random(),
                ];
            }

            return [
                'participant_ids' => $userIds,
                'type'            => 'direct',
                'title'           => 'Direct conversation between buyer and seller',
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
                $participantIds = [$contract->buyer_id, $contract->seller_id];
                $title = "Contract #{$contract->contract_number} Discussion";
            } else {
                $buyers = User::role('buyer')->where('status', 'active')->pluck('id');
                $sellers = User::role('seller')->where('status', 'active')->pluck('id');

                if ($buyers->isNotEmpty() && $sellers->isNotEmpty()) {
                    $participantIds = [$buyers->random(), $sellers->random()];
                } else {
                    $participantIds = User::limit(2)->pluck('id')->toArray();
                }
                $title = 'Contract discussion';
            }

            return [
                'participant_ids' => $participantIds,
                'type'            => 'contract',
                'title'           => $title,
            ];
        });
    }

    public function configure()
    {
        return $this->afterCreating(function (Conversation $conversation) {
            if (! empty($conversation->participant_ids) && is_array($conversation->participant_ids)) {
                $message = Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => fake()->randomElement($conversation->participant_ids),
                ]);

                $conversation->update([
                    'last_message_id'  => $message->id,
                    'last_activity_at' => $message->sent_at,
                ]);
            }
        });
    }
}

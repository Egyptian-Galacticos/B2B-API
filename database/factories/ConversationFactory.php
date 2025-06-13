<?php

namespace Database\Factories;

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
        $allUsers = User::pluck('id');
        $userIds = $allUsers->isNotEmpty()
            ? $allUsers->random(min(2, $allUsers->count()))->toArray()
            : [User::factory()->create()->id, User::factory()->create()->id];

        return [
            'type'             => $this->faker->randomElement(['direct', 'contract']),
            'title'            => $this->faker->sentence(3),
            'participant_ids'  => $userIds,
            'last_message_id'  => null,
            'last_activity_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'is_active'        => $this->faker->boolean(80),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Conversation $conversation) {
            $message = Message::factory()->create([
                'conversation_id' => $conversation->id,
            ]);
            $conversation->update([
                'last_message_id'  => $message->id,
                'last_activity_at' => $message->sent_at,
            ]);
        });
    }
}

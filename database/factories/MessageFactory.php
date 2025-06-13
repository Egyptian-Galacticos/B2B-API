<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $conversations = Conversation::pluck('id');
        $users = User::pluck('id');

        return [
            'conversation_id' => $conversations->isNotEmpty()
                ? $conversations->random()
                : Conversation::factory(),
            'sender_id' => $users->isNotEmpty()
                ? $users->random()
                : User::factory(),
            'content' => $this->faker->paragraph(),
            'type'    => $this->faker->randomElement(['text', 'image', 'file']),
            'sent_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'is_read' => $this->faker->boolean(80),
        ];
    }
}

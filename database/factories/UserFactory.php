<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone_number' => fake()->phoneNumber(),
            'is_email_verified' => fake()->boolean(80), // 80% chance of being verified
            'status' => fake()->randomElement(['active', 'inactive', 'suspended']),
            'profile_image_url' => fake()->optional()->imageUrl(200, 200, 'people'),
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the model's email should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_email_verified' => false,
        ]);
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the user is a seller with recent login.
     */
    public function seller(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_email_verified' => true,
            'last_login_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}

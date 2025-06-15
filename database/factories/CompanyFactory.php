<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'                 => \App\Models\User::factory(),
            'name'                    => fake()->company(),
            'email'                   => fake()->unique()->companyEmail(),
            'tax_id'                  => fake()->numerify('###-##-####'),
            'company_phone'           => fake()->phoneNumber(),
            'commercial_registration' => fake()->numerify('CR-########'),
            'address'                 => [
                'street'      => fake()->streetAddress(),
                'city'        => fake()->city(),
                'state'       => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country'     => fake()->country(),
            ],
            'website'           => fake()->optional()->url(),
            'description'       => fake()->paragraph(),
            'is_email_verified' => fake()->boolean(70), // 70% chance of being verified
        ];
    }

    /**
     * Indicate that the company is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_email_verified' => true,
        ]);
    }

    /**
     * Indicate that the company is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_email_verified' => false,
        ]);
    }

    /**
     * Create company for an existing user.
     */
    public function forUser(\App\Models\User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'email'   => $user->email, // Use user's email as company email
        ]);
    }

    /**
     * Create company with existing user if available.
     */
    public function withExistingUser(): static
    {
        return $this->state(function (array $attributes) {
            $existingUser = \App\Models\User::inRandomOrder()->first();

            return [
                'user_id' => $existingUser ? $existingUser->id : \App\Models\User::factory(),
                'email'   => $existingUser ? $existingUser->email : fake()->unique()->companyEmail(),
            ];
        });
    }
}

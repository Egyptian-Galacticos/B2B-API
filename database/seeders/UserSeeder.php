<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['buyer', 'seller', 'admin'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api']);
        }

        // Create admin user first
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name'        => 'Test',
                'last_name'         => 'Admin',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token'    => Str::random(10),
                'status'            => 'active',
                'is_email_verified' => true,
            ]
        );
        $admin->assignRole('admin');

        // Create admin's company if not exists
        if (! $admin->company) {
            Company::create([
                'user_id'       => $admin->id,
                'name'          => 'Admin Company Ltd',
                'tax_id'        => 'ADM001',
                'email'         => 'admin@company.com',
                'company_phone' => '+1234567890',
                'address'       => [
                    'street'      => '123 Admin Street',
                    'city'        => 'Admin City',
                    'state'       => 'Admin State',
                    'country'     => 'USA',
                    'postal_code' => '12345',
                ],
                'website'           => 'https://admincompany.com',
                'description'       => 'Main administration company',
                'is_email_verified' => true,
            ]);
        }

        // Create sellers (5 active + 2 with different statuses)
        for ($i = 0; $i < 7; $i++) {
            if ($i < 5) {
                // Active verified sellers
                $user = User::create([
                    'first_name'        => fake()->firstName(),
                    'last_name'         => fake()->lastName(),
                    'email'             => fake()->unique()->safeEmail(),
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                    'remember_token'    => Str::random(10),
                    'status'            => 'active',
                    'is_email_verified' => true,
                ]);
                $user->assignRole('seller');

                // Create seller's company
                Company::create([
                    'user_id'       => $user->id,
                    'name'          => fake()->company().' Ltd',
                    'tax_id'        => 'SEL'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'email'         => fake()->unique()->companyEmail(),
                    'company_phone' => fake()->phoneNumber(),
                    'address'       => [
                        'street'      => fake()->streetAddress(),
                        'city'        => fake()->city(),
                        'state'       => fake()->state(),
                        'country'     => fake()->randomElement(['USA', 'Canada', 'UK', 'Germany']),
                        'postal_code' => fake()->postcode(),
                    ],
                    'website'           => fake()->optional(0.7)->url(),
                    'description'       => fake()->optional(0.5)->sentence(10),
                    'is_email_verified' => true,
                ]);
            } else {
                // Edge case sellers
                $statuses = [
                    ['user_status' => 'pending', 'email_verified' => false, 'company_status' => 'pending'],
                    ['user_status' => 'suspended', 'email_verified' => true, 'company_status' => 'verified'],
                ];
                $statusData = $statuses[$i - 5];

                $user = User::create([
                    'first_name'        => fake()->firstName(),
                    'last_name'         => fake()->lastName(),
                    'email'             => fake()->unique()->safeEmail(),
                    'password'          => Hash::make('password'),
                    'email_verified_at' => $statusData['email_verified'] ? now() : null,
                    'remember_token'    => Str::random(10),
                    'status'            => $statusData['user_status'],
                    'is_email_verified' => $statusData['email_verified'],
                ]);
                $user->assignRole('seller');

                Company::create([
                    'user_id'       => $user->id,
                    'name'          => fake()->company().' Ltd',
                    'tax_id'        => 'SEL'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'email'         => fake()->unique()->companyEmail(),
                    'company_phone' => fake()->phoneNumber(),
                    'address'       => [
                        'street'      => fake()->streetAddress(),
                        'city'        => fake()->city(),
                        'state'       => fake()->state(),
                        'country'     => fake()->randomElement(['USA', 'Canada', 'UK', 'Germany']),
                        'postal_code' => fake()->postcode(),
                    ],
                    'website'           => fake()->optional(0.5)->url(),
                    'description'       => fake()->optional(0.3)->sentence(8),
                    'is_email_verified' => $statusData['company_status'] === 'verified',
                ]);
            }
        }

        // Create buyers (8 active + 2 with different statuses)
        for ($i = 0; $i < 10; $i++) {
            if ($i < 8) {
                // Active verified buyers
                $user = User::create([
                    'first_name'        => fake()->firstName(),
                    'last_name'         => fake()->lastName(),
                    'email'             => fake()->unique()->safeEmail(),
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                    'remember_token'    => Str::random(10),
                    'status'            => 'active',
                    'is_email_verified' => true,
                ]);
                $user->assignRole('buyer');

                // Create buyer's company
                Company::create([
                    'user_id'       => $user->id,
                    'name'          => fake()->company().' Corp',
                    'tax_id'        => 'BUY'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'email'         => fake()->unique()->companyEmail(),
                    'company_phone' => fake()->phoneNumber(),
                    'address'       => [
                        'street'      => fake()->streetAddress(),
                        'city'        => fake()->city(),
                        'state'       => fake()->state(),
                        'country'     => fake()->randomElement(['USA', 'Canada', 'UK', 'France', 'Australia']),
                        'postal_code' => fake()->postcode(),
                    ],
                    'website'           => fake()->optional(0.6)->url(),
                    'description'       => fake()->optional(0.4)->sentence(12),
                    'is_email_verified' => true,
                ]);
            } else {
                // Edge case buyers
                $statuses = [
                    ['user_status' => 'pending', 'email_verified' => true, 'company_status' => 'pending'],
                    ['user_status' => 'active', 'email_verified' => false, 'company_status' => 'verified'],
                ];
                $statusData = $statuses[$i - 8];

                $user = User::create([
                    'first_name'        => fake()->firstName(),
                    'last_name'         => fake()->lastName(),
                    'email'             => fake()->unique()->safeEmail(),
                    'password'          => Hash::make('password'),
                    'email_verified_at' => $statusData['email_verified'] ? now() : null,
                    'remember_token'    => Str::random(10),
                    'status'            => $statusData['user_status'],
                    'is_email_verified' => $statusData['email_verified'],
                ]);
                $user->assignRole('buyer');

                Company::create([
                    'user_id'       => $user->id,
                    'name'          => fake()->company().' Corp',
                    'tax_id'        => 'BUY'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'email'         => fake()->unique()->companyEmail(),
                    'company_phone' => fake()->phoneNumber(),
                    'address'       => [
                        'street'      => fake()->streetAddress(),
                        'city'        => fake()->city(),
                        'state'       => fake()->state(),
                        'country'     => fake()->randomElement(['USA', 'Canada', 'UK', 'France', 'Australia']),
                        'postal_code' => fake()->postcode(),
                    ],
                    'website'           => fake()->optional(0.4)->url(),
                    'description'       => fake()->optional(0.2)->sentence(8),
                    'is_email_verified' => $statusData['company_status'] === 'verified',
                ]);
            }
        }
    }
}

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

        $this->createSellers();
        $this->createBuyers();
    }

    private function createSellers(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $email = "seller{$i}@example.com";

            if ($i <= 5) {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name'        => 'Seller',
                        'last_name'         => "User {$i}",
                        'password'          => Hash::make('password'),
                        'email_verified_at' => now(),
                        'remember_token'    => Str::random(10),
                        'status'            => 'active',
                        'is_email_verified' => true,
                    ]
                );
                $user->assignRole('seller');

                if (! $user->company) {
                    Company::create([
                        'user_id'       => $user->id,
                        'name'          => "Seller Company {$i} Ltd",
                        'tax_id'        => 'SEL'.str_pad($i, 3, '0', STR_PAD_LEFT),
                        'email'         => "seller{$i}@company.com",
                        'company_phone' => '+1234567'.str_pad($i, 3, '0', STR_PAD_LEFT),
                        'address'       => [
                            'street'      => "{$i}00 Seller Street",
                            'city'        => "Seller City {$i}",
                            'state'       => "State {$i}",
                            'country'     => 'USA',
                            'postal_code' => '1000'.$i,
                        ],
                        'website'           => "https://seller{$i}.com",
                        'description'       => "Seller company {$i} description",
                        'is_email_verified' => true,
                    ]);
                }
            } else {
                $statuses = [
                    6 => ['user_status' => 'pending', 'email_verified' => false, 'company_status' => 'pending'],
                    7 => ['user_status' => 'suspended', 'email_verified' => true, 'company_status' => 'verified'],
                ];
                $statusData = $statuses[$i];

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name'        => 'Seller',
                        'last_name'         => "User {$i}",
                        'password'          => Hash::make('password'),
                        'email_verified_at' => $statusData['email_verified'] ? now() : null,
                        'remember_token'    => Str::random(10),
                        'status'            => $statusData['user_status'],
                        'is_email_verified' => $statusData['email_verified'],
                    ]
                );
                $user->assignRole('seller');

                if (! $user->company) {
                    Company::create([
                        'user_id'       => $user->id,
                        'name'          => "Seller Company {$i} Ltd",
                        'tax_id'        => 'SEL'.str_pad($i, 3, '0', STR_PAD_LEFT),
                        'email'         => "seller{$i}@company.com",
                        'company_phone' => '+1234567'.str_pad($i, 3, '0', STR_PAD_LEFT),
                        'address'       => [
                            'street'      => "{$i}00 Seller Street",
                            'city'        => "Seller City {$i}",
                            'state'       => "State {$i}",
                            'country'     => 'USA',
                            'postal_code' => '1000'.$i,
                        ],
                        'website'           => $i === 6 ? null : "https://seller{$i}.com",
                        'description'       => "Seller company {$i} description",
                        'is_email_verified' => $statusData['company_status'] === 'verified',
                    ]);
                }
            }
        }
    }

    private function createBuyers(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $email = "buyer{$i}@example.com";

            if ($i <= 8) {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name'        => 'Buyer',
                        'last_name'         => "User {$i}",
                        'password'          => Hash::make('password'),
                        'email_verified_at' => now(),
                        'remember_token'    => Str::random(10),
                        'status'            => 'active',
                        'is_email_verified' => true,
                    ]
                );
                $user->assignRole('buyer');

                if (! $user->company) {
                    Company::create([
                        'user_id'       => $user->id,
                        'name'          => "Buyer Company {$i} Corp",
                        'tax_id'        => 'BUY'.str_pad($i, 3, '0', STR_PAD_LEFT),
                        'email'         => "buyer{$i}@company.com",
                        'company_phone' => '+1234568'.str_pad($i, 3, '0', STR_PAD_LEFT),
                        'address'       => [
                            'street'      => "{$i}00 Buyer Avenue",
                            'city'        => "Buyer City {$i}",
                            'state'       => "State {$i}",
                            'country'     => 'USA',
                            'postal_code' => '2000'.$i,
                        ],
                        'website'           => "https://buyer{$i}.com",
                        'description'       => "Buyer company {$i} description",
                        'is_email_verified' => true,
                    ]);
                }
            } else {
                $statuses = [
                    9  => ['user_status' => 'pending', 'email_verified' => true, 'company_status' => 'pending'],
                    10 => ['user_status' => 'active', 'email_verified' => false, 'company_status' => 'verified'],
                ];
                $statusData = $statuses[$i];

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name'        => 'Buyer',
                        'last_name'         => "User {$i}",
                        'password'          => Hash::make('password'),
                        'email_verified_at' => $statusData['email_verified'] ? now() : null,
                        'remember_token'    => Str::random(10),
                        'status'            => $statusData['user_status'],
                        'is_email_verified' => $statusData['email_verified'],
                    ]
                );
                $user->assignRole('buyer');

                if (! $user->company) {
                    Company::create([
                        'user_id'       => $user->id,
                        'name'          => "Buyer Company {$i} Corp",
                        'tax_id'        => 'BUY'.str_pad($i, 3, '0', STR_PAD_LEFT),
                        'email'         => "buyer{$i}@company.com",
                        'company_phone' => '+1234568'.str_pad($i, 3, '0', STR_PAD_LEFT),
                        'address'       => [
                            'street'      => "{$i}00 Buyer Avenue",
                            'city'        => "Buyer City {$i}",
                            'state'       => "State {$i}",
                            'country'     => 'USA',
                            'postal_code' => '2000'.$i,
                        ],
                        'website'           => $i === 10 ? null : "https://buyer{$i}.com",
                        'description'       => "Buyer company {$i} description",
                        'is_email_verified' => $statusData['company_status'] === 'verified',
                    ]);
                }
            }
        }
    }
}

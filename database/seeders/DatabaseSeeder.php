<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Conversation;
use App\Models\Escrow;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $roles = ['buyer', 'seller', 'admin'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Create or find test admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name'        => 'Test',
                'last_name'         => 'User',
                'password'          => bcrypt('StrongPassword123!'),
                'is_email_verified' => true,
                'status'            => 'active',
            ]
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Create company for admin
        if (! $admin->company) {
            Company::factory()->forUser($admin)->verified()->create();
        }

        // Create additional users with companies and assign roles
        $users = User::factory()->count(10)->create();
        $users->each(function (User $user) {
            $role = ['buyer', 'seller'][array_rand(['buyer', 'seller'])];
            $user->assignRole($role);
            Company::factory()->forUser($user)->create();
        });

        // Get a random seller user
        $seller = $users->filter(fn ($u) => $u->hasRole('seller'))->first();

        // Categories
        $rootCategories = [
            [
                'name'        => 'Electronics',
                'icon'        => 'fa-solid fa-laptop',
                'description' => 'Latest electronics and gadgets',
                'children'    => [
                    'Smartphones' => ['Flagship Phones', 'Budget Phones', 'Accessories'],
                    'Laptops'     => ['Gaming Laptops', 'Business Laptops', 'Ultrabooks'],
                    'Audio'       => ['Headphones', 'Speakers', 'Microphones'],
                    'Cameras'     => ['DSLR', 'Mirrorless', 'Action Cameras'],
                ],
            ],
            [
                'name'        => 'Fashion & Clothing',
                'icon'        => 'fa-solid fa-tshirt',
                'description' => 'Trendy fashion for everyone',
                'children'    => [
                    'Men\'s Fashion'   => ['Shirts', 'Pants', 'Suits', 'Casual Wear'],
                    'Women\'s Fashion' => ['Dresses', 'Tops', 'Skirts', 'Formal Wear'],
                    'Footwear'         => ['Sneakers', 'Formal Shoes', 'Boots', 'Sandals'],
                    'Accessories'      => ['Bags', 'Watches', 'Jewelry', 'Sunglasses'],
                ],
            ],
            [
                'name'        => 'Home & Living',
                'icon'        => 'fa-solid fa-home',
                'description' => 'Everything for your home',
                'children'    => [
                    'Furniture' => ['Living Room', 'Bedroom', 'Dining Room', 'Office'],
                    'Kitchen'   => ['Appliances', 'Cookware', 'Utensils', 'Storage'],
                    'Decor'     => ['Wall Art', 'Lighting', 'Textiles', 'Plants'],
                    'Tools'     => ['Hand Tools', 'Power Tools', 'Garden Tools', 'Safety'],
                ],
            ],
        ];

        foreach ($rootCategories as $rootData) {
            $children = $rootData['children'];
            unset($rootData['children']);

            $rootCategory = Category::factory()
                ->active()
                ->withSeo()
                ->create(array_merge($rootData, [
                    'created_by' => $admin->id,
                    'status'     => 'active',
                ]));

            foreach ($children as $childName => $grandchildren) {
                $childCategory = Category::factory()
                    ->active()
                    ->subcategory($rootCategory)
                    ->create([
                        'name'       => $childName,
                        'created_by' => $admin->id,
                        'status'     => 'active',
                    ]);

                foreach ($grandchildren as $grandchildName) {
                    Category::factory()
                        ->active()
                        ->subcategory($childCategory)
                        ->create([
                            'name'       => $grandchildName,
                            'created_by' => $admin->id,
                            'status'     => 'active',
                        ]);
                }
            }
        }

        // Seller-created pending categories
        if ($seller) {
            Category::factory()
                ->count(5)
                ->pending()
                ->create([
                    'created_by' => $seller->id,
                    'status'     => 'pending',
                ]);

            $randomParent = Category::active()->inRandomOrder()->first();
            if ($randomParent) {
                Category::factory()
                    ->count(3)
                    ->pending()
                    ->subcategory($randomParent)
                    ->create([
                        'created_by' => $seller->id,
                        'status'     => 'pending',
                    ]);
            }
        }

        // Inactive categories by admin
        Category::factory()
            ->count(2)
            ->inactive()
            ->create([
                'created_by' => $admin->id,
                'status'     => 'inactive',
            ]);

        // Products
        Product::factory()->count(5)->withExistingRelationships()->create();

        // Contracts with items
        Contract::factory()->count(3)->active()->create()->each(function (Contract $contract) {
            ContractItem::factory()->count(rand(1, 3))->create(['contract_id' => $contract->id]);
        });

        // Quotes with items
        Quote::factory()->count(3)->sent()->create()->each(function (Quote $quote) {
            QuoteItem::factory()->count(rand(1, 3))->create(['quote_id' => $quote->id]);
        });

        // Others
        Escrow::factory()->count(5)->create();
        Payment::factory()->count(5)->create();
        Conversation::factory()->count(3)->create();
        Message::factory()->count(10)->create();
        MessageAttachment::factory()->count(5)->create();
        Wishlist::factory()->count(5)->create();
        WishlistItem::factory()->count(15)->create();
    }
}

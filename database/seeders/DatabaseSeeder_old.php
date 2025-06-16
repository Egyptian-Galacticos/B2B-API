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
use App\Models\Rfq;
use App\Models\User;
use App\Models\Wishlist;
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
            ['email' => 'anas@gmail.com'],
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

        if (! $admin->company) {
            Company::factory()->forUser($admin)->verified()->create();
        }

        $buyerUsers = User::factory()->count(3)->create();
        $sellerUsers = User::factory()->count(3)->create();

        foreach ($buyerUsers as $user) {
            $user->assignRole('buyer');
            if (! $user->company) {
                Company::factory()->forUser($user)->create();
            }
        }

        foreach ($sellerUsers as $user) {
            $user->assignRole('seller');
            if (! $user->company) {
                Company::factory()->forUser($user)->create();
            }
        }

        $edgeCaseUsers = [
            [
                'email'          => 'unverified.buyer@test.com',
                'role'           => 'buyer',
                'user_states'    => ['is_email_verified' => false, 'status' => 'active'],
                'company_states' => ['is_email_verified' => true],
            ],
            [
                'email'          => 'suspended.seller@test.com',
                'role'           => 'seller',
                'user_states'    => ['is_email_verified' => true, 'status' => 'suspended'],
                'company_states' => ['is_email_verified' => true],
            ],
            [
                'email'          => 'pending.buyer@test.com',
                'role'           => 'buyer',
                'user_states'    => ['is_email_verified' => true, 'status' => 'pending'],
                'company_states' => ['is_email_verified' => false],
            ],
            [
                'email'          => 'pending.seller@test.com',
                'role'           => 'seller',
                'user_states'    => ['is_email_verified' => false, 'status' => 'pending'],
                'company_states' => ['is_email_verified' => false],
            ],
        ];

        foreach ($edgeCaseUsers as $userData) {
            $user = User::factory()->create(array_merge([
                'email'      => $userData['email'],
                'first_name' => 'Test',
                'last_name'  => 'User',
                'password'   => bcrypt('StrongPassword123!'),
            ], $userData['user_states']));

            $user->assignRole($userData['role']);

            Company::factory()->forUser($user)->create($userData['company_states']);
        }

        $allUsers = User::all();
        $sellers = User::role('seller')->get();
        $buyers = User::role('buyer')->get();

        $seller = $sellers->first();

        $rootCategories = [
            [
                'name'        => 'Electronics',
                'icon'        => 'pi pi-desktop',
                'description' => 'Latest electronics and gadgets',
                'children'    => [
                    'Smartphones' => ['Flagship Phones', 'Budget Phones'],
                    'Laptops'     => ['Gaming Laptops', 'Business Laptops'],
                    'Audio'       => ['Headphones', 'Speakers'],
                ],
            ],
            [
                'name'        => 'Fashion & Clothing',
                'icon'        => 'pi pi-shopping-bag',
                'description' => 'Trendy fashion for everyone',
                'children'    => [
                    'Men\'s Fashion'   => ['Shirts', 'Pants'],
                    'Women\'s Fashion' => ['Dresses', 'Tops'],
                    'Footwear'         => ['Sneakers', 'Formal Shoes'],
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

        if ($seller) {
            Category::factory()
                ->count(2)
                ->pending()
                ->create([
                    'created_by' => $seller->id,
                    'status'     => 'pending',
                ]);
        }

        Product::factory()->count(60)->withExistingRelationships()->create()->each(function ($product) {

            $product->addMediaFromUrl('https://picsum.photos/1000')
                ->toMediaCollection('main_image');
            $product->tiers()->create([
                'from_quantity' => 1,
                'to_quantity'   => 10,
                'price'         => $product->price,
            ]);
            $product->tiers()->create([
                'from_quantity' => 11,
                'to_quantity'   => 50,
                'price'         => $product->price * 0.9, // 10% discount
            ]);
            $product->tiers()->create([
                'from_quantity' => 51,
                'to_quantity'   => 100,
                'price'         => $product->price * 0.8, // 20% discount
            ]);
        });

        $testQuotes = Quote::where('status', Quote::STATUS_ACCEPTED)->limit(2)->get();
        if ($testQuotes->count() > 0) {
            foreach ($testQuotes as $quote) {
                $contract = Contract::factory()->active()->create([
                    'buyer_id'  => $quote->rfq->buyer_id,
                    'seller_id' => $quote->rfq->seller_id,
                    'quote_id'  => $quote->id,
                ]);

                $quote->items->each(function ($quoteItem) use ($contract) {
                    ContractItem::factory()->create([
                        'contract_id' => $contract->id,
                        'product_id'  => $quoteItem->product_id,
                        'quantity'    => $quoteItem->quantity,
                        'unit_price'  => $quoteItem->unit_price,
                    ]);
                });
            }
        }

        Contract::factory()->count(1)->create()->each(function (Contract $contract) {
            ContractItem::factory()->count(rand(1, 2))->create(['contract_id' => $contract->id]);
        });

        // Create initial test RFQs with one of each status
        $testBuyer = $buyers->first();
        $testSeller = $sellers->first();
        $testProduct = Product::first();

        if ($testBuyer && $testSeller && $testProduct) {
            $rfqStatuses = [
                Rfq::STATUS_PENDING,
                Rfq::STATUS_SEEN,
                Rfq::STATUS_IN_PROGRESS,
                Rfq::STATUS_QUOTED,
            ];

            foreach ($rfqStatuses as $status) {
                Rfq::factory()->create([
                    'buyer_id'           => $testBuyer->id,
                    'seller_id'          => $testSeller->id,
                    'initial_product_id' => $testProduct->id,
                    'status'             => $status,
                ]);
            }
        }

        // Create additional RFQs with different buyer-seller combinations and varied statuses
        // This will create ~20+ RFQs with realistic distribution of statuses
        $activeProducts = Product::limit(10)->get();

        if ($buyers->count() >= 2 && $sellers->count() >= 2 && $activeProducts->count() > 0) {
            // Create matrix of buyer-seller combinations for realistic test data
            $combinations = [];
            foreach ($buyers->take(3) as $buyer) {
                foreach ($sellers->take(3) as $seller) {
                    $combinations[] = ['buyer' => $buyer, 'seller' => $seller];
                }
            }

            // Create RFQs for each combination with random statuses
            foreach ($combinations as $combo) {
                // Create 1-2 RFQs per buyer-seller combination
                $rfqCount = rand(1, 2);

                for ($i = 0; $i < $rfqCount; $i++) {
                    $randomProduct = $activeProducts->random();

                    // Distribute statuses with realistic probabilities
                    $statusWeights = [
                        Rfq::STATUS_PENDING     => 30,     // 30% - Most common, new requests
                        Rfq::STATUS_SEEN        => 25,        // 25% - Seller has viewed
                        Rfq::STATUS_IN_PROGRESS => 25, // 25% - Being worked on
                        Rfq::STATUS_QUOTED      => 20,      // 20% - Final status
                    ];

                    $randomStatus = $this->getWeightedRandomStatus($statusWeights);

                    Rfq::factory()->create([
                        'buyer_id'           => $combo['buyer']->id,
                        'seller_id'          => $combo['seller']->id,
                        'initial_product_id' => $randomProduct->id,
                        'status'             => $randomStatus,
                        'initial_quantity'   => rand(1, 100),
                        'shipping_country'   => fake()->randomElement(['USA', 'Canada', 'UK', 'Germany', 'France']),
                        'buyer_message'      => fake()->optional(0.7)->sentence(10),
                    ]);
                }
            }

            // Add a few more random RFQs for variety
            for ($i = 0; $i < 5; $i++) {
                $randomBuyer = $buyers->random();
                $randomSeller = $sellers->random();
                $randomProduct = $activeProducts->random();
                $randomStatus = $this->getWeightedRandomStatus([
                    Rfq::STATUS_PENDING     => 30,
                    Rfq::STATUS_SEEN        => 25,
                    Rfq::STATUS_IN_PROGRESS => 25,
                    Rfq::STATUS_QUOTED      => 20,
                ]);

                Rfq::factory()->create([
                    'buyer_id'           => $randomBuyer->id,
                    'seller_id'          => $randomSeller->id,
                    'initial_product_id' => $randomProduct->id,
                    'status'             => $randomStatus,
                    'initial_quantity'   => rand(1, 150),
                    'shipping_country'   => fake()->randomElement(['USA', 'Canada', 'UK', 'Germany', 'France', 'Australia']),
                    'buyer_message'      => fake()->optional(0.6)->paragraph(2),
                ]);
            }
        }

        Rfq::factory()->count(3)->create();

        $testRfqs = Rfq::limit(4)->get();

        if ($testRfqs->count() >= 4) {
            $quoteStatuses = [
                Quote::STATUS_PENDING,
                Quote::STATUS_SENT,
                Quote::STATUS_ACCEPTED,
                Quote::STATUS_REJECTED,
            ];

            foreach ($quoteStatuses as $index => $status) {
                $quote = Quote::factory()->create([
                    'rfq_id' => $testRfqs[$index]->id,
                    'status' => $status,
                ]);

                QuoteItem::factory()
                    ->count(rand(1, 2))
                    ->for($quote)
                    ->create();
            }
        }

        Quote::factory()
            ->count(3)
            ->create()
            ->each(function ($quote) {
                QuoteItem::factory()
                    ->count(rand(1, 2))
                    ->for($quote)
                    ->create();
            });

        Escrow::factory()->count(2)->create();
        Payment::factory()->count(2)->create();
        Conversation::factory()->count(2)->create();
        Message::factory()->count(4)->create();
        MessageAttachment::factory()->count(2)->create();

        // Add some products to wishlists using the pivot table
        $allUsers = User::all();
        $products = Product::all();

        if ($allUsers->count() > 0 && $products->count() > 0) {
            // Add 2-3 products to each user's wishlist
            foreach ($allUsers->take(5) as $user) {
                $randomProducts = $products->shuffle()->take(rand(2, 3));
                foreach ($randomProducts as $product) {
                    // Insert directly into wishlist pivot table
                    try {
                        $user->wishlist()->attach($product->id);
                    } catch (\Exception $e) {
                        // Skip if already exists (duplicate constraint)
                    }
                }
            }
        }
    }

    /**
     * Get a weighted random status based on probability weights
     */
    private function getWeightedRandomStatus(array $statusWeights): string
    {
        $totalWeight = array_sum($statusWeights);
        $randomWeight = rand(1, $totalWeight);
        $currentWeight = 0;

        foreach ($statusWeights as $status => $weight) {
            $currentWeight += $weight;
            if ($randomWeight <= $currentWeight) {
                return $status;
            }
        }

        // Fallback to first status if something goes wrong
        return array_key_first($statusWeights);
    }
}

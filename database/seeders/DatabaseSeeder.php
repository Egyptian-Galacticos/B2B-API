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

        Product::factory()->count(6)->withExistingRelationships()->create();

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

        $testBuyer = $buyers->first();
        $testSeller = $sellers->first();
        $testProduct = Product::first();

        if ($testBuyer && $testSeller && $testProduct) {
            $rfqStatuses = [
                Rfq::STATUS_PENDING,
                Rfq::STATUS_SEEN,
                Rfq::STATUS_IN_PROGRESS,
                Rfq::STATUS_QUOTED,
                Rfq::STATUS_ACCEPTED,
                Rfq::STATUS_REJECTED,
                Rfq::STATUS_CLOSED,
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
        Wishlist::factory()->count(2)->create();

        $wishlists = Wishlist::all();
        $products = Product::all();

        if ($wishlists->count() > 0 && $products->count() > 0) {
            foreach ($wishlists as $wishlist) {
                $availableProducts = $products->shuffle()->take(2);
                foreach ($availableProducts as $product) {
                    WishlistItem::factory()->create([
                        'wishlist_id' => $wishlist->id,
                        'product_id'  => $product->id,
                    ]);
                }
            }
        }
    }
}

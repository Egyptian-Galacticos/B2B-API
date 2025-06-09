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
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $roles = ['buyer', 'seller', 'admin'];
        foreach ($roles as $role) {
            if (! Role::where('name', $role)->exists()) {
                Role::create(['name' => $role]);
            }
        }

        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'test@example.com',
        ]);
        $user->assignRole('admin');

        // Create company for test user
        Company::factory()->forUser($user)->verified()->create();

        // Create additional users with companies
        User::factory()->count(10)->create()->each(function (User $user) {
            // Assign random role
            $roles = ['buyer', 'seller'];
            $user->assignRole($roles[array_rand($roles)]);

            // Create company for each user
            Company::factory()->forUser($user)->create();
        });

        // Create categories with subcategories
        $category = Category::factory()->create();
        $subcategory = Category::factory()->subcategory()->create();

        // Create products with relationships
        Product::factory()->count(5)->withExistingRelationships()->create();

        // Create contracts with items
        Contract::factory()->count(3)->active()->create()->each(function (Contract $contract) {
            ContractItem::factory()->count(rand(1, 3))->create(['contract_id' => $contract->id]);
        });

        // Create quotes with items
        Quote::factory()->count(3)->sent()->create()->each(function (Quote $quote) {
            QuoteItem::factory()->count(rand(1, 3))->create(['quote_id' => $quote->id]);
        });
        Escrow::factory()->count(5)->create();
        Payment::factory()->count(5)->create();
        Conversation::factory()->count(3)->create();
        Message::factory()->count(10)->create();
        MessageAttachment::factory()->count(5)->create();
        Wishlist::factory()->count(5)->create();
        WishlistItem::factory()->count(15)->create();
    }
}

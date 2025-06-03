<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create categories with subcategories
        $category = Category::factory()->create();
        $subcategory = Category::factory()->subcategory()->create();

        // Create products with relationships
        $product = Product::factory()->create();

        // Create contracts with items
        $contract = Contract::factory()->active()->create();
        $contractItem = ContractItem::factory()->create(['contract_id' => $contract->id]);

        // Create quotes with items
        $quote = Quote::factory()->sent()->create();
        $quoteItem = QuoteItem::factory()->create(['quote_id' => $quote->id]);
    }
}

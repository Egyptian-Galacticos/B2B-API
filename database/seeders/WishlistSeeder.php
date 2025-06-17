<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Database\Seeder;

class WishlistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = User::role('buyer')->where('status', 'active')->get();
        $sellers = User::role('seller')->where('status', 'active')->get();
        $users = $buyers->merge($sellers);

        $products = Product::where('is_active', true)->get();

        if ($users->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($users->take(8) as $user) {
            $wishlistProductCount = rand(2, 5);
            $selectedProducts = $products->shuffle()->take($wishlistProductCount);

            foreach ($selectedProducts as $product) {
                try {
                    $user->wishlist()->attach($product->id, [
                        'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
                        'updated_at' => now(),
                    ]);
                } catch (Exception $e) {
                    continue;
                }
            }
        }
    }
}

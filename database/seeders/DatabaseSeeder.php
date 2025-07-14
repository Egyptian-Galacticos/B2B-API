<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            DummyJsonProductSeeder::class,
            //            ProductSeeder::class,
            RfqSeeder::class,
        ]);

        Conversation::factory()->betweenBuyerAndSeller()->count(5)->create();

        $this->call([
            QuoteSeeder::class,
            ContractSeeder::class,
            WishlistSeeder::class,
        ]);

        if (Contract::count() > 0) {
            Conversation::factory()->forContract()->count(2)->create();
        }

        $conversations = Conversation::whereNotNull('seller_id')->whereNotNull('buyer_id')->get();
        foreach ($conversations as $conversation) {
            $participantIds = [$conversation->seller_id, $conversation->buyer_id];

            Message::factory()->count(rand(2, 5))->create([
                'conversation_id' => $conversation->id,
                'sender_id'       => fake()->randomElement($participantIds),
            ]);
        }

    }
}

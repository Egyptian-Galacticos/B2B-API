<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Escrow;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Payment;
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
            ProductSeeder::class,
            RfqSeeder::class,
            QuoteSeeder::class,
            WishlistSeeder::class,
        ]);

        // Create random data using factories
        Escrow::factory()->count(2)->create();
        Payment::factory()->count(2)->create();
        Conversation::factory()->count(2)->create();
        Message::factory()->count(4)->create();
        MessageAttachment::factory()->count(2)->create();
    }
}

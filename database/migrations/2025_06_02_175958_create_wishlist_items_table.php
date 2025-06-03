<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')
                ->constrained('wishlists')
                ->onDelete('cascade')
                ->index();
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->index();
            $table->timestamp('added_at')->useCurrent()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['wishlist_id', 'product_id']); // no duplicate products in the wishlist
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};

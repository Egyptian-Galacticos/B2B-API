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
            $table->unsignedBigInteger('wishlist_id')->index();
            $table->foreign('wishlist_id', 'fk_wishlist_items_wishlist_id')
                ->references('id')
                ->on('wishlists')
                ->onDelete('cascade');

            $table->unsignedBigInteger('product_id')->index();
            $table->foreign('product_id', 'fk_wishlist_items_product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->dateTime('added_at')->index();
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

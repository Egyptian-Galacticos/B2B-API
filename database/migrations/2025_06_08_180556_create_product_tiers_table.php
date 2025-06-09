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
        Schema::create('product_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('from_quantity', 10, 2); // Minimum quantity
            $table->decimal('to_quantity', 10, 2); // Maximum quantity
            $table->decimal('price', 10, 2); // Price for the tier
            $table->string('currency', 10)->default('USD'); // Currency for the price
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_tiers');
    }
};

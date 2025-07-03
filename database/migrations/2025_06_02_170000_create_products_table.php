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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('brand')->default('Generic');
            $table->string('model_number')->nullable();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('hs_code')->nullable();
            //            $table->decimal('price', 10, 2);
            $table->string('currency', 10);
            $table->string('origin');
            $table->json('dimensions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('sample_available')->default(false);
            $table->decimal('sample_price', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    // $table->string('brand')->nullable();
    // $table->string('model_number')->nullable();
    // $table->decimal('weight', 10, 3)->nullable(); // in kg
    // $table->string('weight_unit')->default('kg');
    // $table->json('images')->nullable();
    // $table->json('documents')->nullable(); // Technical sheets, certificates     Downloadable file and their names and certificate name
    //    //Tiers product id  - from(units) - to(units) - price (New Table)
    // $table->boolean('sample_available')->default(false);
    // $table->decimal('sample_price', 10, 2)->nullable();
    // $table->fullText(['name', 'description', 'sku']); // For search

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

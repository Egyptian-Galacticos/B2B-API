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
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users');
            $table->foreignId('seller_id')->constrained('users');

            $table->foreignId('initial_product_id')->constrained('products');
            $table->integer('initial_quantity')->default(1);
            $table->string('shipping_country')->nullable();
            $table->string('shipping_address')->nullable();
            $table->text('buyer_message')->nullable();
            $table->enum('status', ['Pending', 'Seen', 'In Progress', 'Quoted'])->default('Pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index(['deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};

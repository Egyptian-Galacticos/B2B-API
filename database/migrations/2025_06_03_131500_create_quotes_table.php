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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');  // or buyers table
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade'); // or sellers table
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 10);
            $table->timestamp('valid_until');
            $table->text('terms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};

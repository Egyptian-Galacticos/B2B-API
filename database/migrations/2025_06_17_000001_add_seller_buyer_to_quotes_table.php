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
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('seller_id')
                ->nullable()
                ->after('rfq_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('buyer_id')
                ->nullable()
                ->after('seller_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->index(['seller_id', 'buyer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['buyer_id']);
            $table->dropForeign(['seller_id']);
            $table->dropIndex(['seller_id', 'buyer_id']);
            $table->dropColumn(['seller_id', 'buyer_id']);
        });
    }
};

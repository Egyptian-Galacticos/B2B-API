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
        Schema::table('quote_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();

            $table->dropForeign(['product_id']);

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            // Drop the SET NULL foreign key constraint
            $table->dropForeign(['product_id']);

            // Make the column non-nullable again
            $table->foreignId('product_id')->nullable(false)->change();

            // Restore the original foreign key constraint
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });
    }
};

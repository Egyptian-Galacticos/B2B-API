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
        Schema::table('rfqs', function (Blueprint $table) {
            $table->foreignId('initial_product_id')->nullable()->change();

            $table->dropForeign(['initial_product_id']);

            $table->foreign('initial_product_id')
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
        Schema::table('rfqs', function (Blueprint $table) {
            // Drop the SET NULL foreign key constraint
            $table->dropForeign(['initial_product_id']);

            // Make the column non-nullable again
            $table->foreignId('initial_product_id')->nullable(false)->change();

            // Restore the original foreign key constraint
            $table->foreign('initial_product_id')
                ->references('id')
                ->on('products');
        });
    }
};

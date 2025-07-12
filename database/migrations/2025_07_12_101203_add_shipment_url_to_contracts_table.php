<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('shipment_url')->nullable()->after('seller_transaction_id');

            DB::statement("ALTER TABLE contracts MODIFY
            COLUMN status ENUM('pending_approval','approved', 'pending_payment', 'pending_payment_confirmation', 'buyer_payment_rejected','in_progress','delivered_and_paid',
                'shipped', 'verify_shipment_url', 'delivered', 'completed', 'cancelled') NOT NULL DEFAULT 'pending_approval'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['shipment_url']);

            DB::statement("ALTER TABLE contracts MODIFY
            COLUMN status ENUM('pending_approval','approved', 'pending_payment', 'pending_payment_confirmation', 'buyer_payment_rejected','in_progress','delivered_and_paid',
                'shipped', 'delivered', 'completed', 'cancelled') NOT NULL DEFAULT 'pending_approval'");
        });
    }
};

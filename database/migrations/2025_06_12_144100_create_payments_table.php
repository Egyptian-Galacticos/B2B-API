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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();

            $table->foreign('contract_id', 'fk_payments_contract_id')
                ->references('id')
                ->on('contracts')
                ->onDelete('cascade');
            $table->enum('type', ['escrow_release', 'direct', 'refund'])->default('direct')->index();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending')->index();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD')->index();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

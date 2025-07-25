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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

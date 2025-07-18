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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->index();
            $table->foreign('conversation_id', 'fk_messages_conversation_id')
                ->references('id')
                ->on('conversations')
                ->onDelete('cascade');
            $table->foreignId('sender_id')->index();
            $table->foreign('sender_id', 'fk_messages_sender_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->text('content')->nullable();
            $table->enum('type', ['text', 'image', 'file'])->default('text')->index();
            $table->timestamp('sent_at')->useCurrent()->index();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

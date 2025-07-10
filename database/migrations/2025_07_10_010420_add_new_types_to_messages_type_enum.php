<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'rfq', 'quote', 'contract') NOT NULL DEFAULT 'text'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the new enum values and revert to original
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file') NOT NULL DEFAULT 'text'");
    }
};

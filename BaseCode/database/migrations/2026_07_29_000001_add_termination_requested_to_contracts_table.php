<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the status ENUM in contracts table to include 'termination_requested'
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'awaiting_upload', 'active', 'signed', 'pending', 'expired', 'cancelled', 'termination_requested', 'terminated') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'awaiting_upload', 'active', 'signed', 'pending', 'expired', 'cancelled', 'terminated') DEFAULT 'draft'");
    }
};

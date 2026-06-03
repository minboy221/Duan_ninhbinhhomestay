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
        DB::statement("ALTER TABLE `rooms` MODIFY COLUMN `status` ENUM('available','rented','maintenance','deposited','expiring_soon','pending_renewal','suspended','under_construction') NOT NULL DEFAULT 'available'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms_status_enum', function (Blueprint $table) {
            //
        });
    }
};

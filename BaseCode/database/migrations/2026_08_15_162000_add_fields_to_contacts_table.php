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
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('contacts', 'ticket_code')) {
                $table->string('ticket_code')->nullable()->unique()->after('user_id');
            }
            if (!Schema::hasColumn('contacts', 'category')) {
                $table->string('category')->default('general')->after('subject');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('contacts', 'ticket_code')) {
                $table->dropColumn('ticket_code');
            }
            if (Schema::hasColumn('contacts', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};

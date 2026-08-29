<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('profile_unlock_reason')->nullable()->after('last_profile_update_at');
            $table->timestamp('profile_unlock_requested_at')->nullable()->after('profile_unlock_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_unlock_reason', 'profile_unlock_requested_at']);
        });
    }

};

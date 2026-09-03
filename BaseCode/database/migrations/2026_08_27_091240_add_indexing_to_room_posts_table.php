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
        Schema::table('room_posts', function (Blueprint $table) {
            $table->index(['status', 'bumped_at', 'published_at'], 'idx_room_posts_bump_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_posts', function (Blueprint $table) {
            $table->dropIndex('idx_room_posts_bump_sort');
        });
    }
};

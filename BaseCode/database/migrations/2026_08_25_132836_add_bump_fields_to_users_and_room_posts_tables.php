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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'bump_credits')) {
                $table->integer('bump_credits')->default(0)->after('status')->comment('Số lượt đẩy tin còn lại của chủ trọ');
            }
            if (!Schema::hasColumn('users', 'package_name')) {
                $table->string('package_name')->nullable()->after('bump_credits')->comment('Tên gói đẩy tin đang hoạt động');
            }
        });

        Schema::table('room_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('room_posts', 'bumped_at')) {
                $table->timestamp('bumped_at')->nullable()->after('published_at')->comment('Thời điểm đẩy tin lên đầu trang');
            }
            if (!Schema::hasColumn('room_posts', 'bump_count')) {
                $table->integer('bump_count')->default(0)->after('bumped_at')->comment('Số lần tin này đã được đẩy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bump_credits', 'package_name']);
        });

        Schema::table('room_posts', function (Blueprint $table) {
            $table->dropColumn(['bumped_at', 'bump_count']);
        });
    }
};

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
        // Chỉ thêm cột address nếu cột này chưa tồn tại
        if (!Schema::hasColumn('rooms', 'address')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('address')->nullable()->after('room_number')->comment('địa chỉ cụ thể của phòng trọ');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rooms', 'address')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('address');
            });
        }
    }

};

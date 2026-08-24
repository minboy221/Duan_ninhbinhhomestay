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
        if (Schema::hasTable('room_service') && !Schema::hasColumn('room_service', 'price')) {
            Schema::table('room_service', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->nullable()->after('service_id')->comment('Giá dịch vụ riêng cho phòng');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('room_service') && Schema::hasColumn('room_service', 'price')) {
            Schema::table('room_service', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm cột map_embed cho bảng areas để lưu mã nhúng Google Maps
     */
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->text('map_embed')->nullable()->after('icon')->comment('Mã nhúng iframe Google Maps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('map_embed');
        });
    }
};

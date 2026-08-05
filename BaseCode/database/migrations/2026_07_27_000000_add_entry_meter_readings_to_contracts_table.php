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
        Schema::table('contracts', function (Blueprint $table) {
            $table->integer('entry_elec_index')->nullable()->comment('Chỉ số điện ban đầu lúc nhận phòng');
            $table->string('entry_elec_image')->nullable()->comment('Ảnh công tơ điện ban đầu');
            $table->integer('entry_water_index')->nullable()->comment('Chỉ số nước ban đầu lúc nhận phòng');
            $table->string('entry_water_image')->nullable()->comment('Ảnh công tơ nước ban đầu');
            $table->timestamp('entry_readings_submitted_at')->nullable()->comment('Thời điểm gửi chỉ số ban đầu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'entry_elec_index',
                'entry_elec_image',
                'entry_water_index',
                'entry_water_image',
                'entry_readings_submitted_at',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations
     */
    public function up()
    {
        Schema::create('landlord_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('users')->onDelete('cascade');

            // Đảm bảo tên khóa ngoại trỏ đúng tên bảng cơ sở trọ của bạn (ví dụ: boarding_houses)
            $table->foreignId('boarding_house_id')->constrained('boarding_houses')->onDelete('cascade');

            $table->tinyInteger('day_of_week')->comment('0: Chủ nhật, 1: Thứ 2, ..., 6: Thứ 7');
            $table->time('start_time'); // Ví dụ: 08:00:00
            $table->time('end_time');   // Ví dụ: 11:30:00
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_availabilities');
    }
};

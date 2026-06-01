<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * BẢNG LƯU THÔNG TIN XÁC MINH ĐĂNG KÝ của chủ trọ
     */
    public function up(): void
    {
        Schema::create('boarding_houses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('name')->comment('tên cơ sở trọ');
            $table->string('district')->comment('Quận/phường');
            $table->string('address_detail')->comment('địa chỉ chi tiết');
            //lưu nhiều ảnh 
            $table->json('contract_images')->nullable()->comment('đường dẫn mảng ảnh hợp đồng');
            $table->json('room_images')->nullable()->comment('đường dẫn ảnh không gian của trọ');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boarding_houses');
    }
};

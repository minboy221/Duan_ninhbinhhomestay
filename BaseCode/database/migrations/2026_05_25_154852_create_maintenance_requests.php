<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * BẢNG YÊU CẦU SỬA CHỮA, BÁO CÁO SỰ CỐ CỦA NGƯỜI THUÊ
     */
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id')->comment('Liên kết tới bảng rooms');
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('image_path')->nullable()->comment('Hình ảnh chụp sự cố hư hỏng');
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'cancelled'])->default('pending');
            $table->decimal('repair_cost', 10, 2)->nullable()->comment('Chi phí sửa chữa nếu có');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};

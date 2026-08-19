<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BẢNG TIỆN ÍCH CỦA PHÒNG TRỌ
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('cascade')->comment('Null nếu là dịch vụ chung của hệ thống');
            $table->string('name');
            $table->decimal('price',10,2);
            $table->timestamp('price_updated_at')->nullable()->comment('Thời gian cập nhật giá gần nhất');
            $table->enum('type',['per_kwh','per_m3','fixed','per_person'])->default('fixed')->comment('Cách tính: theo số điện, số nước, cố định, hoặc theo đầu người');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

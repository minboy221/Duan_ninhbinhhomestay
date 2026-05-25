<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BẢNG CHI TIẾT PHÒNG
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->string('room_number');
            $table->decimal('price',10,2);
            $table->decimal('area',8,2)->comment('diện tích phòng theo m2');
            $table->integer('capacity')->default(2)->comment('số người ở tối đa');
            $table->enum('status',['available','rented','maintenance'])->default('available');
            $table->text('amenities')->nullable()->comment('tiện ích đi kèm');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

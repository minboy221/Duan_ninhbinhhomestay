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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boarding_house_id')->constrained('boarding_houses')->onDelete('cascade');
            $table->foreignId('floor_id')->nullable()->constrained('floors')->onDelete('set null');
            $table->string('room_number');
            $table->string('address')->nullable()->comment('địa chỉ cụ thể phòng trọ');
            $table->decimal('price', 10 ,2);
            $table ->decimal('area',8,2)->comment('diện tích phòng theo m2');
            $table->integer('capacity')->default(2)->comment('số người ở tối đa');
            $table->enum('status',['available','rented','maintenance','depoosited','suspended'])->default('available');
            $table->string('maintenance_reason')->nullable();
            $table->json('images')->nullable()->comment('Mảng đường dẫn ảnh gốc của phòng');
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

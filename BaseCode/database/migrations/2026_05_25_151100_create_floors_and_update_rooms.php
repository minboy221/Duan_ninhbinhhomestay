<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Bảng tầng: thuộc về 1 property
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->string('name')->comment('Tên tầng: Tầng 1, Tầng 2...');
            $table->integer('sort_order')->default(0)->comment('Thứ tự sắp xếp');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['floor_id']);
            $table->dropColumn(['floor_id', 'images']);
        });
        Schema::dropIfExists('floors');
    }
};

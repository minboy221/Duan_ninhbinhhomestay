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
        Schema::create('roommate_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('tetant_id')->constrained('users')->onDelete('cascade'); //người gửi yêu cầu
            $table->string('type')->default('stranger');
            $table->string('status')->default('pending');
            $table->string('new_resident_name')->nullable();
            $table->string('new_resident_phone')->nullable();
            $table->string('new_resident_email')->nullable();
            $table->string('new_resident_cccd')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roommate_requests');
    }
};

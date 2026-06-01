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
        Schema::create('user_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('id_card_number')->nullable();
            $table->string('id_card_front')->nullable(); // Đường dẫn ảnh mặt trước
            $table->string('id_card_back')->nullable();  // Đường dẫn ảnh mặt sau
            $table->string('face_auth_image')->nullable(); // Ảnh chụp selfie từ camera
            $table->enum('kyc_status', ['unverified', 'pending', 'approved', 'rejected'])->default('unverified');
            $table->text('kyc_notes')->nullable(); // Lý do từ chối nếu có
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_verifications');
    }
};

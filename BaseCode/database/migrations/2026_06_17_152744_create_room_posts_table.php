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
        Schema::create('room_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('users')->onDelete('cascade')->comment('tên của người chủ trọ đăng tin');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade')->comment('tin đăng này tiếp thị cho căn phòng cụ thể nào đó');
            $table->string('title')->comment('tiêu đề của tin đăng');
            $table->text('description')->comment('Nội dung mô tả cho tiết bài đăng quảng cáo phòng');
            $table->json('image')->nullable()->comment('phần ảnh quảng cáo do chủ trọ tự chụp up lên');
            $table->enum('status',['draft','pending','approved','rejected','hidden','expired'])->default('pending');
            $table->string('reject_reason')->nullable()->comment('lý do admin từ chối');
            $table->integer('view_count')->default(0);
            $table->boolean('is_vip')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_posts');
    }
};

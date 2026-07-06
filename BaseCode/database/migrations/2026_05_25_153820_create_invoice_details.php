<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * BẢNG CHI TIẾT HOÁ ĐƠN
     */
    public function up(): void
    {
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null')->comment('null nếu tiền phòng gốc');
            $table->string('item_name')->comment('tên khoản phí (tiền phòng, tiền điện, tiền nước');
            $table->integer('old_index')->nullable()->comment('chỉ số cũ đối với điện, nước');
            $table->integer('new_index')->nullable()->comment('chỉ số mới điện, nước');
            $table->string('meter_image_path')->nullable()->comment('Đường dẫn ảnh chụp công tơ điện/nước lúc chốt số');
            $table->integer('quantity')->default(1)->comment('Số lượng tiêu thụ');
            $table->decimal('price', 10, 2)->comment('đơn giá tại thời điểm chốt');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Bổ sung thêm các trạng thái mới cho phòng trọ:
     * - deposited: Đã đặt cọc
     * - expiring_soon: Sắp hết hợp đồng
     * - pending_renewal: Chờ gia hạn
     * - suspended: Tạm ngưng cho thuê
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `rooms` MODIFY COLUMN `status` ENUM('available','rented','maintenance','deposited','expiring_soon','pending_renewal','suspended') NOT NULL DEFAULT 'available'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `rooms` MODIFY COLUMN `status` ENUM('available','rented','maintenance') NOT NULL DEFAULT 'available'");
    }
};

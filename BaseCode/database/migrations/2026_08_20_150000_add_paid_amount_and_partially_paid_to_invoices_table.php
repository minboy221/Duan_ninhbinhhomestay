<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('total_amount');
            }
        });

        // Cập nhật enum status hỗ trợ thêm partially_paid
        try {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('unpaid', 'partially_paid', 'paid', 'overdue') DEFAULT 'unpaid'");
        } catch (\Throwable $e) {
            // Trường hợp chạy SQLite hoặc driver khác
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });

        try {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('unpaid', 'paid', 'overdue') DEFAULT 'unpaid'");
        } catch (\Throwable $e) {
        }
    }
};

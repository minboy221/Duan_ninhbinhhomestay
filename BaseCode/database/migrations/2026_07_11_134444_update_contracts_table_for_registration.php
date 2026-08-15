<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'monthly_rent')) {
                $table->decimal('monthly_rent', 10, 2)->after('end_date')->default(0)->comment('giá thuê hàng tháng (thỏa thuận)');
            }
            if (!Schema::hasColumn('contracts', 'signed_contract_image')) {
                $table->string('signed_contract_image')->nullable()->after('contract_file_path')->comment('ảnh chụp hợp đồng đã ký');
            }
        });

        // Modify the status ENUM
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'awaiting_upload', 'active', 'signed', 'pending', 'expired', 'cancelled') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the ENUM
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('pending', 'signed', 'expired', 'cancelled') DEFAULT 'pending'");
        
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['monthly_rent', 'signed_contract_image']);
        });
    }
};

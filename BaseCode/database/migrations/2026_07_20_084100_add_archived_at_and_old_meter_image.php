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
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('paid_at');
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->string('old_meter_image_path')->nullable()->after('meter_image_path')->comment('Đường dẫn ảnh chụp công tơ điện/nước kỳ cũ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->dropColumn('old_meter_image_path');
        });
    }
};

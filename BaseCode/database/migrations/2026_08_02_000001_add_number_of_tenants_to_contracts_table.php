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
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'number_of_tenants')) {
                $table->unsignedInteger('number_of_tenants')->default(1)->after('deposit_amount')->comment('Số lượng người ở theo hợp đồng');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'number_of_tenants')) {
                $table->dropColumn('number_of_tenants');
            }
        });
    }
};

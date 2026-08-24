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
        Schema::table('invoice_details', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_details', 'old_index')) {
                $table->integer('old_index')->nullable()->comment('chỉ số cũ đối với điện, nước')->after('item_name');
            }
            if (!Schema::hasColumn('invoice_details', 'new_index')) {
                $table->integer('new_index')->nullable()->comment('chỉ số mới điện, nước')->after('old_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_details', 'old_index')) {
                $table->dropColumn('old_index');
            }
            if (Schema::hasColumn('invoice_details', 'new_index')) {
                $table->dropColumn('new_index');
            }
        });
    }
};

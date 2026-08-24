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
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->comment('Tên ngân hàng');
            $table->string('bank_account_no')->nullable()->comment('Số tài khoản');
            $table->string('bank_account_name')->nullable()->comment('Tên chủ tài khoản');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_no', 'bank_account_name']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('services', 'price_updated_at')) {
            Schema::table('services', function (Blueprint $table) {
                $table->timestamp('price_updated_at')->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'price_updated_at')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('price_updated_at');
            });
        }
    }
};

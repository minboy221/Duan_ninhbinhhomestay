<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop from rooms
        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'latitude')) {
                $table->dropColumn('latitude');
            }
            if (Schema::hasColumn('rooms', 'longitude')) {
                $table->dropColumn('longitude');
            }
        });

        // Add to floors
        Schema::table('floors', function (Blueprint $table) {
            $table->string('address')->nullable()->after('name');
            $table->decimal('latitude', 10, 8)->nullable()->after('address');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        // Revert floors
        Schema::table('floors', function (Blueprint $table) {
            $table->dropColumn(['address', 'latitude', 'longitude']);
        });

        // Revert rooms
        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('address');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }
};

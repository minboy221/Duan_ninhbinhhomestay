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
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable()->change();
            if (!Schema::hasColumn('rooms', 'boarding_house_id')) {
                $table->foreignId('boarding_house_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('boarding_houses')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'boarding_house_id')) {
                $table->dropForeign(['boarding_house_id']);
                $table->dropColumn('boarding_house_id');
            }
        });
    }
};

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
            if (!Schema::hasColumn('contracts', 'ocr_status')) {
                $table->string('ocr_status')->default('pending')->after('status');
            }
            if (!Schema::hasColumn('contracts', 'ocr_rejection_reason')) {
                $table->text('ocr_rejection_reason')->nullable()->after('ocr_status');
            }
            if (!Schema::hasColumn('contracts', 'terms_accepted')) {
                $table->boolean('terms_accepted')->default(false)->after('ocr_rejection_reason');
            }
            if (!Schema::hasColumn('contracts', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted');
            }
            if (!Schema::hasColumn('contracts', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('terms_accepted_at');
            }
            if (!Schema::hasColumn('contracts', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->after('cancellation_reason');
            }
            if (!Schema::hasColumn('contracts', 'liquidated_at')) {
                $table->timestamp('liquidated_at')->nullable()->after('cancelled_by');
            }
            if (!Schema::hasColumn('contracts', 'deposit_refund_amount')) {
                $table->decimal('deposit_refund_amount', 10, 2)->nullable()->after('liquidated_at');
            }
            if (!Schema::hasColumn('contracts', 'deposit_handling')) {
                $table->string('deposit_handling')->nullable()->after('deposit_refund_amount');
            }
        });

        // Modify the status ENUM to include 'terminated'
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'awaiting_upload', 'active', 'signed', 'pending', 'expired', 'cancelled', 'terminated') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn([
                'ocr_status',
                'ocr_rejection_reason',
                'terms_accepted',
                'terms_accepted_at',
                'cancellation_reason',
                'cancelled_by',
                'liquidated_at',
                'deposit_refund_amount',
                'deposit_handling',
            ]);
        });
    }
};

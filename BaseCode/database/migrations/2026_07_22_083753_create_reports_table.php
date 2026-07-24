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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->numericMorphs('reportable');
            //nội dung báo cáo
            $table->string('reason');
            $table->text('description');
            $table->json('evidence_images')->nullable();
            $table->enum('status', ['pending', 'investigating', 'resolved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            
            // Trường phục vụ tự thương lượng và phản hồi
            $table->timestamp('negotiation_deadline')->nullable();
            $table->boolean('target_resolved')->default(false);
            $table->boolean('reporter_resolved')->default(false);
            $table->text('response_note')->nullable();
            $table->json('response_evidence')->nullable();

            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

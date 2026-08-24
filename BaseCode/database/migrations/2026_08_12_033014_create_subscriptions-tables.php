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
        //bảng các gói dịch vụ
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('duration_days')->default(30);
            $table->string('badge')->nullable();
            $table->integer('sort_order')->default(0)->comment('Thứ tự sắp xếp');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        //bảng các tính năng
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('feature_code', 100)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        //bảng trung gian gói tính năng
        Schema::create('subscription_plan_feature', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('features')->onDelete('cascade');
            $table->string('feature_value')->nullable()->comment('10,50,unlimited, true, false...');
            $table->primary(['plan_id', 'feature_id']);
        });

        //bảng lịch sử đăng ký gói của chủ trọ
        Schema::create('landlord_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->string('payment_code', 50)->nullable()->unique();
            $table->decimal('price_at_purchase', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('payment_method', 50)->default('vietqr');
            $table->string('status', 30)->default('pending')->comment('pending|active|rejected|expired');
            $table->text('admin_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_subscriptions');
        Schema::dropIfExists('subscription_plan_feature');
        Schema::dropIfExists('features');
        Schema::dropIfExists('subscription_plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('point_voucher_redemptions');
        Schema::dropIfExists('points_vouchers');

        Schema::create('points_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('retail_customer_group_id')->nullable();
            $table->decimal('points_required', 12, 2)->default(0);
            $table->decimal('voucher_value', 12, 2)->default(0);
            $table->string('usage_method')->nullable();
            $table->string('branch')->nullable();
            $table->unsignedInteger('valid_days')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('retail_customer_group_id')
                ->references('id')
                ->on('retail_customer_groups')
                ->nullOnDelete();
        });

        Schema::create('point_voucher_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('points_voucher_id')->constrained('points_vouchers')->cascadeOnDelete();
            $table->string('order_no')->unique();
            $table->string('customer_name');
            $table->string('account_no')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('voucher_value', 12, 2)->default(0);
            $table->decimal('points_spent', 12, 2)->default(0);
            $table->string('usage_method')->nullable();
            $table->string('branch')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_voucher_redemptions');
        Schema::dropIfExists('points_vouchers');
    }
};

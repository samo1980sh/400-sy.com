<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coupon_redemptions')) {
            return;
        }

        Schema::create('coupon_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('order_no')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('currency', 20)->nullable();
            $table->string('status', 20)->default('pending');
            $table->dateTime('applied_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['coupon_id', 'customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};

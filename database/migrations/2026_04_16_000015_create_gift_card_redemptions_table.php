<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gift_card_redemptions')) {
            return;
        }

        Schema::create('gift_card_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gift_card_id')->constrained('gift_cards')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('order_no')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount_used', 12, 2)->default(0);
            $table->decimal('balance_before', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->dateTime('applied_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['gift_card_id', 'customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_redemptions');
    }
};
